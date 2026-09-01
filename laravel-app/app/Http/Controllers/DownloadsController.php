<?php

namespace App\Http\Controllers;

use App\Models\AppRelease;
use App\Models\User;
use App\Services\AppReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadsController extends Controller
{
    public function __construct(private readonly AppReleaseService $releases) {}

    public function index()
    {
        return view('admin.downloads.index', [
            'releases' => AppRelease::query()->android()->with('uploader:id,name')->orderByDesc('version_code')->get(),
            'current' => AppRelease::current(),
            'canManage' => $this->canManage(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'apk' => ['required', 'file', 'max:204800'],
            'version_name' => ['required', 'string', 'max:40', 'regex:/^[0-9][0-9A-Za-z.\-]*$/'],
            'version_code' => ['required', 'integer', 'min:1', 'unique:app_releases,version_code'],
            'channel' => ['required', 'in:PRODUCTION,BETA'],
            'release_notes' => ['nullable', 'string', 'max:2000'],
            'is_current' => ['nullable', 'boolean'],
            'is_mandatory' => ['nullable', 'boolean'],
            'notify_users' => ['nullable', 'boolean'],
        ]);

        if (strtolower((string) $request->file('apk')->getClientOriginalExtension()) !== 'apk') {
            return back()->withErrors(['apk' => 'The uploaded file must be an .apk build.'])->withInput();
        }

        $release = $this->releases->publish($request->file('apk'), [
            'version_name' => $data['version_name'],
            'version_code' => $data['version_code'],
            'channel' => $data['channel'],
            'release_notes' => $data['release_notes'] ?? null,
            'is_current' => $request->boolean('is_current'),
            'is_mandatory' => $request->boolean('is_mandatory'),
        ], $request->user());

        $message = 'Release '.$release->label.' uploaded successfully.';

        if ($request->boolean('notify_users')) {
            $recipients = $this->releases->announce($release);
            $message .= ' Update notification sent to '.$recipients.' tenant'.($recipients === 1 ? '' : 's').'.';
        }

        return redirect()->route('admin.downloads.index')->with('success', $message);
    }

    public function makeCurrent(AppRelease $release)
    {
        $this->authorizeManage();
        $this->releases->makeCurrent($release);

        return back()->with('success', $release->label.' is now the current download.');
    }

    public function notify(AppRelease $release)
    {
        $this->authorizeManage();
        $recipients = $this->releases->announce($release);

        return back()->with('success', 'Update notification sent to '.$recipients.' tenant'.($recipients === 1 ? '' : 's').'.');
    }

    public function destroy(AppRelease $release)
    {
        $this->authorizeManage();

        if ($release->is_current) {
            return back()->with('error', 'Promote another release to current before deleting this one.');
        }

        $label = $release->label;
        $this->releases->delete($release);

        return back()->with('success', 'Release '.$label.' deleted.');
    }

    public function downloadRelease(AppRelease $release): BinaryFileResponse
    {
        return $this->streamRelease($release);
    }

    /** Public endpoint: always serves whichever release is flagged current. */
    public function publicDownloadApk(): BinaryFileResponse
    {
        $release = AppRelease::current();

        if (! $release) {
            abort(404, 'No app release is currently published.');
        }

        return $this->streamRelease($release);
    }

    public function downloadApk(): BinaryFileResponse
    {
        return $this->publicDownloadApk();
    }

    /** Consumed by the Android app to detect newer builds. */
    public function latestVersion()
    {
        $release = AppRelease::current();

        if (! $release) {
            return response()->json(['available' => false]);
        }

        return response()->json([
            'available' => true,
            'version_name' => $release->version_name,
            'version_code' => $release->version_code,
            'channel' => $release->channel,
            'release_notes' => $release->release_notes,
            'is_mandatory' => $release->is_mandatory,
            'file_size' => $release->file_size,
            'checksum' => $release->checksum,
            'download_url' => route('downloads.apk.public'),
            'released_at' => $release->created_at?->toISOString(),
        ]);
    }

    private function streamRelease(AppRelease $release): BinaryFileResponse
    {
        abort_unless($release->exists(), 404, 'The build file for this release is missing on the server.');

        $release->increment('download_count');

        return response()->download($release->absolutePath(), $release->file_name, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only super administrators can manage app releases.');
    }

    private function canManage(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->role?->name === 'SUPER_ADMIN';
    }
}
