package com.tenantpro.app.data.api;

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
public final class AuthInterceptor_Factory implements Factory<AuthInterceptor> {
  private final Provider<DataStoreManager> dataStoreManagerProvider;

  public AuthInterceptor_Factory(Provider<DataStoreManager> dataStoreManagerProvider) {
    this.dataStoreManagerProvider = dataStoreManagerProvider;
  }

  @Override
  public AuthInterceptor get() {
    return newInstance(dataStoreManagerProvider.get());
  }

  public static AuthInterceptor_Factory create(
      Provider<DataStoreManager> dataStoreManagerProvider) {
    return new AuthInterceptor_Factory(dataStoreManagerProvider);
  }

  public static AuthInterceptor newInstance(DataStoreManager dataStoreManager) {
    return new AuthInterceptor(dataStoreManager);
  }
}
