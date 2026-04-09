package com.tenantpro.app.ui.maintenance;

import com.tenantpro.app.data.repository.TenantFeatureRepository;
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
public final class MaintenanceViewModel_Factory implements Factory<MaintenanceViewModel> {
  private final Provider<TenantFeatureRepository> repositoryProvider;

  public MaintenanceViewModel_Factory(Provider<TenantFeatureRepository> repositoryProvider) {
    this.repositoryProvider = repositoryProvider;
  }

  @Override
  public MaintenanceViewModel get() {
    return newInstance(repositoryProvider.get());
  }

  public static MaintenanceViewModel_Factory create(
      Provider<TenantFeatureRepository> repositoryProvider) {
    return new MaintenanceViewModel_Factory(repositoryProvider);
  }

  public static MaintenanceViewModel newInstance(TenantFeatureRepository repository) {
    return new MaintenanceViewModel(repository);
  }
}
