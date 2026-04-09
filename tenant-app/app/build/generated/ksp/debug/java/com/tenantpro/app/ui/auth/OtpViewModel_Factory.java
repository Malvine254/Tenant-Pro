package com.tenantpro.app.ui.auth;

import com.tenantpro.app.data.repository.AuthRepository;
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
public final class OtpViewModel_Factory implements Factory<OtpViewModel> {
  private final Provider<AuthRepository> authRepositoryProvider;

  public OtpViewModel_Factory(Provider<AuthRepository> authRepositoryProvider) {
    this.authRepositoryProvider = authRepositoryProvider;
  }

  @Override
  public OtpViewModel get() {
    return newInstance(authRepositoryProvider.get());
  }

  public static OtpViewModel_Factory create(Provider<AuthRepository> authRepositoryProvider) {
    return new OtpViewModel_Factory(authRepositoryProvider);
  }

  public static OtpViewModel newInstance(AuthRepository authRepository) {
    return new OtpViewModel(authRepository);
  }
}
