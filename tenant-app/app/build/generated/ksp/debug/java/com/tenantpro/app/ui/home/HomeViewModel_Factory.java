package com.tenantpro.app.ui.home;

import com.tenantpro.app.data.repository.AuthRepository;
import com.tenantpro.app.data.repository.InvoiceRepository;
import com.tenantpro.app.utils.DataStoreManager;
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
public final class HomeViewModel_Factory implements Factory<HomeViewModel> {
  private final Provider<AuthRepository> authRepositoryProvider;

  private final Provider<InvoiceRepository> invoiceRepositoryProvider;

  private final Provider<DataStoreManager> dataStoreProvider;

  private final Provider<NetworkConnectivityObserver> connectivityProvider;

  public HomeViewModel_Factory(Provider<AuthRepository> authRepositoryProvider,
      Provider<InvoiceRepository> invoiceRepositoryProvider,
      Provider<DataStoreManager> dataStoreProvider,
      Provider<NetworkConnectivityObserver> connectivityProvider) {
    this.authRepositoryProvider = authRepositoryProvider;
    this.invoiceRepositoryProvider = invoiceRepositoryProvider;
    this.dataStoreProvider = dataStoreProvider;
    this.connectivityProvider = connectivityProvider;
  }

  @Override
  public HomeViewModel get() {
    return newInstance(authRepositoryProvider.get(), invoiceRepositoryProvider.get(), dataStoreProvider.get(), connectivityProvider.get());
  }

  public static HomeViewModel_Factory create(Provider<AuthRepository> authRepositoryProvider,
      Provider<InvoiceRepository> invoiceRepositoryProvider,
      Provider<DataStoreManager> dataStoreProvider,
      Provider<NetworkConnectivityObserver> connectivityProvider) {
    return new HomeViewModel_Factory(authRepositoryProvider, invoiceRepositoryProvider, dataStoreProvider, connectivityProvider);
  }

  public static HomeViewModel newInstance(AuthRepository authRepository,
      InvoiceRepository invoiceRepository, DataStoreManager dataStore,
      NetworkConnectivityObserver connectivity) {
    return new HomeViewModel(authRepository, invoiceRepository, dataStore, connectivity);
  }
}
