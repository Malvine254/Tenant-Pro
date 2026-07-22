import { readdirSync, statSync } from 'fs'
import { join } from 'path'
import { NextResponse } from 'next/server'

export async function GET() {
  try {
    const apkDir = join(process.cwd(), 'public', 'apk')
    const files = readdirSync(apkDir)
    
    const apks = files
      .filter(file => file.endsWith('.apk'))
      .map(filename => {
        const filePath = join(apkDir, filename)
        const stat = statSync(filePath)
        
        return {
          filename,
          size: formatBytes(stat.size),
          buildType: filename.includes('release') ? 'Release' : 'Debug',
          modifiedDate: new Date(stat.mtime).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
          }),
          downloadUrl: `/apk/${filename}`,
        }
      })
      .sort((a, b) => b.modifiedDate.localeCompare(a.modifiedDate))
    
    return NextResponse.json({ apks })
  } catch (error) {
    console.error('APK list error:', error)
    return NextResponse.json(
      { error: 'Failed to fetch APK list' },
      { status: 500 }
    )
  }
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}
