package com.tenantpro.app.ui.queries;

import com.tenantpro.app.data.repository.TenantFeatureRepository;
import com.tenantpro.app.utils.DataStoreManager;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.QualifierMetadata;
import dagger.internal.ScopeMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;

@ScopeMetadata
@QualifierMetadata
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
public final class QueriesViewModel_Factory implements Factory<QueriesViewModel> {
  private final Provider<DataStoreManager> dataStoreManagerProvider;

  private final Provider<TenantFeatureRepository> repositoryProvider;

  public QueriesViewModel_Factory(Provider<DataStoreManager> dataStoreManagerProvider,
      Provider<TenantFeatureRepository> repositoryProvider) {
    this.dataStoreManagerProvider = dataStoreManagerProvider;
    this.repositoryProvider = repositoryProvider;
  }

  @Override
  public QueriesViewModel get() {
    return newInstance(dataStoreManagerProvider.get(), repositoryProvider.get());
  }

  public static QueriesViewModel_Factory create(Provider<DataStoreManager> dataStoreManagerProvider,
      Provider<TenantFeatureRepository> repositoryProvider) {
    return new QueriesViewModel_Factory(dataStoreManagerProvider, repositoryProvider);
  }

  public static QueriesViewModel newInstance(DataStoreManager dataStoreManager,
      TenantFeatureRepository repository) {
    return new QueriesViewModel(dataStoreManager, repository);
  }
}
