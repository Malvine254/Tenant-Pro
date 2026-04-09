package com.tenantpro.app.ui.history;

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
public final class PaymentHistoryViewModel_Factory implements Factory<PaymentHistoryViewModel> {
  private final Provider<PaymentRepository> paymentRepositoryProvider;

  public PaymentHistoryViewModel_Factory(Provider<PaymentRepository> paymentRepositoryProvider) {
    this.paymentRepositoryProvider = paymentRepositoryProvider;
  }

  @Override
  public PaymentHistoryViewModel get() {
    return newInstance(paymentRepositoryProvider.get());
  }

  public static PaymentHistoryViewModel_Factory create(
      Provider<PaymentRepository> paymentRepositoryProvider) {
    return new PaymentHistoryViewModel_Factory(paymentRepositoryProvider);
  }

  public static PaymentHistoryViewModel newInstance(PaymentRepository paymentRepository) {
    return new PaymentHistoryViewModel(paymentRepository);
  }
}
