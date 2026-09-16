package com.tenantpro.app.data.model

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class AppUpdateInfoTest {

    @Test
    fun `update is available only for a higher version code with a download URL`() {
        assertTrue(update(versionCode = 6).isNewerThan(currentVersionCode = 5))
        assertFalse(update(versionCode = 5).isNewerThan(currentVersionCode = 5))
        assertFalse(update(versionCode = 4).isNewerThan(currentVersionCode = 5))
        assertFalse(update(versionCode = 6, downloadUrl = null).isNewerThan(currentVersionCode = 5))
        assertFalse(update(versionCode = 6, available = false).isNewerThan(currentVersionCode = 5))
    }

    private fun update(
        versionCode: Int,
        available: Boolean = true,
        downloadUrl: String? = "https://app.starmaxltd.com/download/apk"
    ) = AppUpdateInfo(
        available = available,
        versionCode = versionCode,
        downloadUrl = downloadUrl
    )
}