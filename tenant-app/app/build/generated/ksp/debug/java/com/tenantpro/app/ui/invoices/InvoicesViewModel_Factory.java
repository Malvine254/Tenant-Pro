package com.tenantpro.app.ui.invoices;

import com.tenantpro.app.data.repository.AuthRepository;
import com.tenantpro.app.data.repository.InvoiceRepository;
import com.tenantpro.app.utils.NetworkConnectivityObserver;
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
public final class InvoicesViewModel_Factory implements Factory<InvoicesViewModel> {
  private final Provider<InvoiceRepository> invoiceRepositoryProvider;

  private final Provider<AuthRepository> authRepositoryProvider;

  private final Provider<NetworkConnectivityObserver> connectivityProvider;

  public InvoicesViewModel_Factory(Provider<InvoiceRepository> invoiceRepositoryProvider,
      Provider<AuthRepository> authRepositoryProvider,
      Provider<NetworkConnectivityObserver> connectivityProvider) {
    this.invoiceRepositoryProvider = invoiceRepositoryProvider;
    this.authRepositoryProvider = authRepositoryProvider;
    this.connectivityProvider = connectivityProvider;
  }

  @Override
  public InvoicesViewModel get() {
    return newInstance(invoiceRepositoryProvider.get(), authRepositoryProvider.get(), connectivityProvider.get());
  }

  public static InvoicesViewModel_Factory create(
      Provider<InvoiceRepository> invoiceRepositoryProvider,
      Provider<AuthRepository> authRepositoryProvider,
      Provider<NetworkConnectivityObserver> connectivityProvider) {
    return new InvoicesViewModel_Factory(invoiceRepositoryProvider, authRepositoryProvider, connectivityProvider);
  }

  public static InvoicesViewModel newInstance(InvoiceRepository invoiceRepository,
      AuthRepository authRepository, NetworkConnectivityObserver connectivity) {
    return new InvoicesViewModel(invoiceRepository, authRepository, connectivity);
  }
}
