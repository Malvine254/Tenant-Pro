package com.tenantpro.app.ui.rental;

import com.tenantpro.app.data.repository.AuthRepository;
import com.tenantpro.app.data.repository.InvoiceRepository;
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
public final class RentalInfoViewModel_Factory implements Factory<RentalInfoViewModel> {
  private final Provider<AuthRepository> authRepositoryProvider;

  private final Provider<InvoiceRepository> invoiceRepositoryProvider;

  public RentalInfoViewModel_Factory(Provider<AuthRepository> authRepositoryProvider,
      Provider<InvoiceRepository> invoiceRepositoryProvider) {
    this.authRepositoryProvider = authRepositoryProvider;
    this.invoiceRepositoryProvider = invoiceRepositoryProvider;
  }

  @Override
  public RentalInfoViewModel get() {
    return newInstance(authRepositoryProvider.get(), invoiceRepositoryProvider.get());
  }

  public static RentalInfoViewModel_Factory create(Provider<AuthRepository> authRepositoryProvider,
      Provider<InvoiceRepository> invoiceRepositoryProvider) {
    return new RentalInfoViewModel_Factory(authRepositoryProvider, invoiceRepositoryProvider);
  }

  public static RentalInfoViewModel newInstance(AuthRepository authRepository,
      InvoiceRepository invoiceRepository) {
    return new RentalInfoViewModel(authRepository, invoiceRepository);
  }
}
