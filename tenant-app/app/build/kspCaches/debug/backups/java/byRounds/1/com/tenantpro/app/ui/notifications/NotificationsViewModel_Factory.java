package com.tenantpro.app.ui.notifications;

import android.content.Context;
import com.tenantpro.app.data.repository.TenantFeatureRepository;
import com.tenantpro.app.utils.DataStoreManager;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.QualifierMetadata;
import dagger.internal.ScopeMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;

@ScopeMetadata
@QualifierMetadata("dagger.hilt.android.qualifiers.ApplicationContext")
@DaggerGenerated
@Generated(
    value = "dagger.internal.codegen.ComponentProcessor",
    comments = "https://dagger.dev"
)
@SuppressWarnings({
    "unchecked",
    "rawtypes",
    "KotlinInternal",
    "KotlinInternalInJava",
    "cast",
    "deprecation"
})
public final class NotificationsViewModel_Factory implements Factory<NotificationsViewModel> {
  private final Provider<TenantFeatureRepository> repositoryProvider;

  private final Provider<DataStoreManager> dataStoreManagerProvider;

  private final Provider<Context> contextProvider;

  public NotificationsViewModel_Factory(Provider<TenantFeatureRepository> repositoryProvider,
      Provider<DataStoreManager> dataStoreManagerProvider, Provider<Context> contextProvider) {
    this.repositoryProvider = repositoryProvider;
    this.dataStoreManagerProvider = dataStoreManagerProvider;
    this.contextProvider = contextProvider;
  }

  @Override
  public NotificationsViewModel get() {
    return newInstance(repositoryProvider.get(), dataStoreManagerProvider.get(), contextProvider.get());
  }

  public static NotificationsViewModel_Factory create(
      Provider<TenantFeatureRepository> repositoryProvider,
      Provider<DataStoreManager> dataStoreManagerProvider, Provider<Context> contextProvider) {
    return new NotificationsViewModel_Factory(repositoryProvider, dataStoreManagerProvider, contextProvider);
  }

  public static NotificationsViewModel newInstance(TenantFeatureRepository repository,
      DataStoreManager dataStoreManager, Context context) {
    return new NotificationsViewModel(repository, dataStoreManager, context);
  }
}
