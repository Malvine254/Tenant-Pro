package com.tenantpro.app.di

import android.content.Context
import androidx.room.Room
import androidx.room.migration.Migration
import androidx.sqlite.db.SupportSQLiteDatabase
import com.tenantpro.app.data.local.dao.CachedResponseDao
import com.tenantpro.app.data.local.db.AppDatabase
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DatabaseModule {

    private val MIGRATION_1_2 = object : Migration(1, 2) {
        override fun migrate(db: SupportSQLiteDatabase) {
            db.execSQL(
                """CREATE TABLE IF NOT EXISTS `cached_responses` (
                    `userId` TEXT NOT NULL,
                    `cacheKey` TEXT NOT NULL,
                    `payload` TEXT NOT NULL,
                    `updatedAt` INTEGER NOT NULL,
                    PRIMARY KEY(`userId`, `cacheKey`)
                )""".trimIndent()
            )
        }
    }

    private val MIGRATION_2_3 = object : Migration(2, 3) {
        override fun migrate(db: SupportSQLiteDatabase) {
            // Version 2 response payloads and the unused legacy invoice table
            // were plaintext. Purge them so only encrypted cache is retained.
            db.execSQL("DELETE FROM `cached_responses`")
            db.execSQL("DROP TABLE IF EXISTS `invoices`")
        }
    }

    @Provides
    @Singleton
    fun provideAppDatabase(@ApplicationContext context: Context): AppDatabase =
        Room.databaseBuilder(context, AppDatabase::class.java, "tenantpro.db")
            .addMigrations(MIGRATION_1_2, MIGRATION_2_3)
            .build()

    @Provides
    @Singleton
    fun provideCachedResponseDao(db: AppDatabase): CachedResponseDao = db.cachedResponseDao()
}
