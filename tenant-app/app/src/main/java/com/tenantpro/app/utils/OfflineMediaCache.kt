package com.tenantpro.app.utils

import android.content.Context
import com.bumptech.glide.Glide
import com.tenantpro.app.data.model.UserProfile
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OfflineMediaCache @Inject constructor(
    @ApplicationContext private val context: Context
) {
    suspend fun cacheProfileMedia(profile: UserProfile) = withContext(Dispatchers.IO) {
        val tenancies = profile.tenantProfiles.ifEmpty { listOfNotNull(profile.tenantProfile) }
        val urls = buildSet {
            profile.profileImageUrl?.let(::add)
            tenancies.forEach { tenancy ->
                tenancy.unit?.imageUrls.orEmpty().forEach(::add)
                tenancy.unit?.displayImageUrl?.let(::add)
                tenancy.unit?.interiorGallery.orEmpty().map { it.url }.forEach(::add)
                tenancy.unit?.property?.coverImageUrl?.let(::add)
            }
        }.filter { it.isNotBlank() }

        urls.forEach { url ->
            runCatching {
                Glide.with(context)
                    .downloadOnly()
                    .load(url.toAbsoluteAssetUrl())
                    .submit()
                    .get()
            }
        }
    }
}