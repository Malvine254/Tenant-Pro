<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadsController extends Controller
{
    private const APK_FILENAME = 'app-debug.apk';

    /**
     * Show downloads page
     */
    public function index()
    {
        $apkPath = public_path('downloads/'.self::APK_FILENAME);
        $apkExists = file_exists($apkPath);
        $apkSize = $apkExists ? filesize($apkPath) : null;

        return view('admin.downloads.index', [
            'apkExists' => $apkExists,
            'apkSize' => $apkSize,
        ]);
    }

    /**
     * Download the APK file
     */
    public function downloadApk()
    {
        return $this->downloadDebugApk('APK file not found. Please contact support.');
    }

    /**
     * Public download endpoint (no auth required)
     */
    public function publicDownloadApk()
    {
        return $this->downloadDebugApk('APK file not found');
    }

    private function downloadDebugApk(string $notFoundMessage)
    {
        $apkPath = public_path('downloads/'.self::APK_FILENAME);

        if (!file_exists($apkPath)) {
            abort(404, $notFoundMessage);
        }

        return response()->download($apkPath, self::APK_FILENAME, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="'.self::APK_FILENAME.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
