package com.tenantpro.app.di

import android.content.Context
import androidx.room.Room
import com.tenantpro.app.data.local.dao.InvoiceDao
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

    @Provides
    @Singleton
    fun provideAppDatabase(@ApplicationContext context: Context): AppDatabase =
        Room.databaseBuilder(context, AppDatabase::class.java, "tenantpro.db")
            .fallbackToDestructiveMigration()
            .build()

    @Provides
    @Singleton
    fun provideInvoiceDao(db: AppDatabase): InvoiceDao = db.invoiceDao()
}
