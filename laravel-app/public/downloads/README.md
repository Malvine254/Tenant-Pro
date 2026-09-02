# App Downloads

This folder contains the Android APK file for Starmax Tenant Services.

## Updating the APK

When you rebuild the Android app and want to update the download available in the admin panel:

### On Windows:
```powershell
cd tenant-app
./gradlew assembleDebug
```

### On Linux/Mac:
```bash
cd tenant-app
./gradlew assembleDebug
```

`assembleDebug` automatically copies the build output to
`laravel-app/public/downloads/app-debug.apk`. The file is tracked by Git, so
run `git status` after every build and commit the updated APK with the source changes.

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
