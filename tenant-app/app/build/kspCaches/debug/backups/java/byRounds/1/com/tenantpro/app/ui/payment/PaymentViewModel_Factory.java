package com.tenantpro.app.ui.payment;

import com.tenantpro.app.data.repository.AuthRepository;
import com.tenantpro.app.data.repository.PaymentRepository;
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
public final class PaymentViewModel_Factory implements Factory<PaymentViewModel> {
  private final Provider<PaymentRepository> paymentRepositoryProvider;

  private final Provider<AuthRepository> authRepositoryProvider;

  public PaymentViewModel_Factory(Provider<PaymentRepository> paymentRepositoryProvider,
      Provider<AuthRepository> authRepositoryProvider) {
    this.paymentRepositoryProvider = paymentRepositoryProvider;
    this.authRepositoryProvider = authRepositoryProvider;
  }

  @Override
  public PaymentViewModel get() {
    return newInstance(paymentRepositoryProvider.get(), authRepositoryProvider.get());
  }

  public static PaymentViewModel_Factory create(
      Provider<PaymentRepository> paymentRepositoryProvider,
      Provider<AuthRepository> authRepositoryProvider) {
    return new PaymentViewModel_Factory(paymentRepositoryProvider, authRepositoryProvider);
  }

  public static PaymentViewModel newInstance(PaymentRepository paymentRepository,
      AuthRepository authRepository) {
    return new PaymentViewModel(paymentRepository, authRepository);
  }
}
