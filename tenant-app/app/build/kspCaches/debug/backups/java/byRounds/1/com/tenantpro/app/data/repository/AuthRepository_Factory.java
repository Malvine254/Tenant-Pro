package com.tenantpro.app.data.repository;

import com.tenantpro.app.data.api.ApiService;
import com.tenantpro.app.utils.DataStoreManager;
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
public final class AuthRepository_Factory implements Factory<AuthRepository> {
  private final Provider<ApiService> apiProvider;

  private final Provider<DataStoreManager> dataStoreProvider;

  public AuthRepository_Factory(Provider<ApiService> apiProvider,
      Provider<DataStoreManager> dataStoreProvider) {
    this.apiProvider = apiProvider;
    this.dataStoreProvider = dataStoreProvider;
  }

  @Override
  public AuthRepository get() {
    return newInstance(apiProvider.get(), dataStoreProvider.get());
  }

  public static AuthRepository_Factory create(Provider<ApiService> apiProvider,
      Provider<DataStoreManager> dataStoreProvider) {
    return new AuthRepository_Factory(apiProvider, dataStoreProvider);
  }

  public static AuthRepository newInstance(ApiService api, DataStoreManager dataStore) {
    return new AuthRepository(api, dataStore);
  }
}
