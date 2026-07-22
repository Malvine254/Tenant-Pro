# App Downloads

This folder contains the Android APK file for TenantPro.

## Updating the APK

When you rebuild the Android app and want to update the download available in the admin panel:

### On Windows:
```powershell
cd tenant-app
./gradlew assembleDebug
copy app\build\outputs\apk\debug\app-debug.apk ..\laravel-app\public\downloads\
```

### On Linux/Mac:
```bash
cd tenant-app
./gradlew assembleDebug
cp app/build/outputs/apk/debug/app-debug.apk ../laravel-app/public/downloads/
```

Then commit and push to GitHub:
```bash
git add laravel-app/public/downloads/app-debug.apk
git commit -m "Update Android APK"
git push
```

## Why is the APK here?

The Android `build/` folder is ignored by Git (`.gitignore`), so we store the APK in this version-controlled folder instead. This ensures:
- The APK is deployed to production via Git
- Users can download it from the admin panel without login
- The file persists across deployments

## File Size

Keep an eye on the APK size. If it grows too large, consider alternatives like storing it in a cloud storage service (AWS S3, Azure Blob Storage) and linking to it instead.
