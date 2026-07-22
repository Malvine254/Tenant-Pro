<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadsController extends Controller
{
    /**
     * Show downloads page
     */
    public function index()
    {
        $apkPath = public_path('downloads/app-debug.apk');
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
        $apkPath = public_path('downloads/app-debug.apk');

        if (!file_exists($apkPath)) {
            abort(404, 'APK file not found. Please contact support.');
        }

        return response()->download($apkPath, 'TenantPro-App.apk');
    }

    /**
     * Public download endpoint (no auth required)
     */
    public function publicDownloadApk()
    {
        $apkPath = public_path('downloads/app-debug.apk');

        if (!file_exists($apkPath)) {
            abort(404, 'APK file not found');
        }

        return response()->download($apkPath, 'TenantPro-App.apk');
    }
}
