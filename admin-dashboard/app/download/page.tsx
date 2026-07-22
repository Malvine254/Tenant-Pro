'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';

interface ApkInfo {
  filename: string;
  size: number;
  sizeInMB: string;
  lastModified: string;
}

export default function PublicApkDownloadPage() {
  const [apkInfo, setApkInfo] = useState<ApkInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [downloading, setDownloading] = useState(false);

  useEffect(() => {
    const fetchApkInfo = async () => {
      try {
        const response = await fetch('/api/downloads/apk-info');
        if (!response.ok) {
          throw new Error('APK not found. Please build the Android app first.');
        }
        const data = await response.json();
        setApkInfo(data);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to fetch APK info');
      } finally {
        setLoading(false);
      }
    };

    fetchApkInfo();
  }, []);

  const handleDownload = async () => {
    setDownloading(true);
    try {
      const response = await fetch('/api/downloads/apk');
      if (!response.ok) {
        throw new Error('Download failed');
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'tenant-pro-app.apk';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Download failed');
    } finally {
      setDownloading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-8">
      <div className="max-w-2xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <Link href="/" className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-4 inline-block">
            ← Back Home
          </Link>
          <h1 className="text-4xl font-bold text-slate-900 mt-4">Download Tenant Pro</h1>
          <p className="text-slate-600 mt-2">
            Get the mobile app for managing your tenancy
          </p>
        </div>

        {/* Main Card */}
        <div className="bg-white rounded-lg shadow-lg p-8 border border-slate-200">
          {loading && (
            <div className="text-center py-12">
              <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              <p className="text-slate-600 mt-4">Loading APK information...</p>
            </div>
          )}

          {error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
              <h3 className="text-red-900 font-semibold mb-2">Error</h3>
              <p className="text-red-800">{error}</p>
            </div>
          )}

          {apkInfo && !loading && (
            <div className="space-y-6">
              {/* App Icon & Name */}
              <div className="flex items-center space-x-4 pb-6 border-b border-slate-200">
                <div className="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center">
                  <span className="text-2xl font-bold text-white">TP</span>
                </div>
                <div>
                  <h2 className="text-2xl font-bold text-slate-900">Tenant Pro</h2>
                  <p className="text-slate-600">Property Management & Payments</p>
                </div>
              </div>

              {/* APK Details */}
              <div className="grid grid-cols-2 gap-4">
                <div className="bg-slate-50 rounded-lg p-4">
                  <p className="text-sm text-slate-600 font-medium">File Size</p>
                  <p className="text-2xl font-bold text-slate-900">{apkInfo.sizeInMB} MB</p>
                </div>
                <div className="bg-slate-50 rounded-lg p-4">
                  <p className="text-sm text-slate-600 font-medium">File Name</p>
                  <p className="text-sm font-mono text-slate-900 break-all">{apkInfo.filename}</p>
                </div>
              </div>

              {/* Last Updated */}
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-900">
                  <strong>Last Updated:</strong> {new Date(apkInfo.lastModified).toLocaleString()}
                </p>
              </div>

              {/* Instructions */}
              <div className="bg-slate-50 rounded-lg p-4">
                <h3 className="font-semibold text-slate-900 mb-3">Installation Instructions</h3>
                <ol className="space-y-2 text-sm text-slate-700">
                  <li>1. Download the APK file using the button below</li>
                  <li>2. Enable &quot;Unknown Sources&quot; in your device settings</li>
                  <li>3. Open the APK file to install the app</li>
                  <li>4. Login with your tenant credentials</li>
                </ol>
              </div>

              {/* Download Button */}
              <button
                onClick={handleDownload}
                disabled={downloading}
                className="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:from-slate-400 disabled:to-slate-500 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 flex items-center justify-center space-x-2"
              >
                {downloading ? (
                  <>
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span>Downloading...</span>
                  </>
                ) : (
                  <>
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Download APK</span>
                  </>
                )}
              </button>

              {/* Alternative Link */}
              <p className="text-center text-sm text-slate-600">
                Or{' '}
                <a
                  href="/api/downloads/apk"
                  className="text-blue-600 hover:text-blue-800 font-medium"
                >
                  direct download link
                </a>
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
