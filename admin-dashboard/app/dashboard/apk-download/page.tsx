'use client'

import { useEffect, useState } from 'react'

interface APKInfo {
  filename: string
  size: string
  buildType: string
  modifiedDate: string
  downloadUrl: string
}

export default function APKDownloadPage() {
  const [apks, setApks] = useState<APKInfo[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    fetchAPKs()
  }, [])

  const fetchAPKs = async () => {
    try {
      setLoading(true)
      const response = await fetch('/api/apk/list')
      if (!response.ok) throw new Error('Failed to fetch APKs')
      const data = await response.json()
      setApks(data.apks || [])
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-6">
      <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6 rounded-lg shadow-lg">
        <h1 className="text-3xl font-bold mb-2">Mobile App Downloads</h1>
        <p className="text-blue-100">Download the latest Tenant Pro Android app (APK)</p>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          ⚠️ {error}
        </div>
      )}

      {loading ? (
        <div className="flex justify-center items-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
        </div>
      ) : apks.length === 0 ? (
        <div className="bg-gray-50 border border-gray-200 text-gray-600 px-4 py-8 rounded-lg text-center">
          <p>No APK builds available yet.</p>
          <p className="text-sm text-gray-500 mt-2">Once Android builds are generated, they will appear here.</p>
        </div>
      ) : (
        <div className="grid gap-4">
          {apks.map((apk) => (
            <div
              key={apk.filename}
              className="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow"
            >
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    📱 {apk.filename}
                    <span className="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                      {apk.buildType}
                    </span>
                  </h3>
                  <p className="text-sm text-gray-600 mt-2">
                    Size: <span className="font-medium">{apk.size}</span> • Modified:{' '}
                    <span className="font-medium">{apk.modifiedDate}</span>
                  </p>
                </div>
                <a
                  href={apk.downloadUrl}
                  download={apk.filename}
                  className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors flex-shrink-0"
                >
                  ⬇️ Download
                </a>
              </div>
            </div>
          ))}
        </div>
      )}

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 className="font-semibold text-blue-900 mb-2">Installation Instructions</h3>
        <ol className="text-sm text-blue-800 space-y-2 list-decimal list-inside">
          <li>Download the APK file to your Android device</li>
          <li>Open your file manager and locate the downloaded APK</li>
          <li>Tap the APK file to install (you may need to allow unknown sources)</li>
          <li>Follow the on-screen prompts to complete installation</li>
          <li>Launch the app and log in with your credentials</li>
        </ol>
      </div>
    </div>
  )
}
