<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadsController extends Controller
{
    /**
     * Show downloads page
     */
    public function index()
    {
        $apkPath = base_path('../../tenant-app/app/build/outputs/apk/debug/app-debug.apk');
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
        $apkPath = base_path('../../tenant-app/app/build/outputs/apk/debug/app-debug.apk');

        if (!file_exists($apkPath)) {
            abort(404, 'APK file not found. Please ensure the app has been built.');
        }

        return response()->download($apkPath, 'TenantPro-App.apk');
    }

    /**
     * Public download endpoint (no auth required)
     */
    public function publicDownloadApk()
    {
        $apkPath = base_path('../../tenant-app/app/build/outputs/apk/debug/app-debug.apk');

        if (!file_exists($apkPath)) {
            abort(404, 'APK file not found');
        }

        return response()->download($apkPath, 'TenantPro-App.apk');
    }
}
