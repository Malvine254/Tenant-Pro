package com.tenantpro.app.data.repository;

import com.tenantpro.app.data.api.ApiService;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.QualifierMetadata;
import dagger.internal.ScopeMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;

@ScopeMetadata("javax.inject.Singleton")
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
public final class TenantFeatureRepository_Factory implements Factory<TenantFeatureRepository> {
  private final Provider<ApiService> apiProvider;

  public TenantFeatureRepository_Factory(Provider<ApiService> apiProvider) {
    this.apiProvider = apiProvider;
  }

  @Override
  public TenantFeatureRepository get() {
    return newInstance(apiProvider.get());
  }

  public static TenantFeatureRepository_Factory create(Provider<ApiService> apiProvider) {
    return new TenantFeatureRepository_Factory(apiProvider);
  }

  public static TenantFeatureRepository newInstance(ApiService api) {
    return new TenantFeatureRepository(api);
  }
}
