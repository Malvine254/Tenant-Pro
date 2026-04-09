package com.tenantpro.app.ui.account;

import com.tenantpro.app.data.repository.AuthRepository;
import com.tenantpro.app.data.repository.InvoiceRepository;
import com.tenantpro.app.utils.DataStoreManager;
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
public final class AccountSettingsViewModel_Factory implements Factory<AccountSettingsViewModel> {
  private final Provider<AuthRepository> authRepositoryProvider;

  private final Provider<InvoiceRepository> invoiceRepositoryProvider;

  private final Provider<DataStoreManager> dataStoreManagerProvider;

  public AccountSettingsViewModel_Factory(Provider<AuthRepository> authRepositoryProvider,
      Provider<InvoiceRepository> invoiceRepositoryProvider,
      Provider<DataStoreManager> dataStoreManagerProvider) {
    this.authRepositoryProvider = authRepositoryProvider;
    this.invoiceRepositoryProvider = invoiceRepositoryProvider;
    this.dataStoreManagerProvider = dataStoreManagerProvider;
  }

  @Override
  public AccountSettingsViewModel get() {
    return newInstance(authRepositoryProvider.get(), invoiceRepositoryProvider.get(), dataStoreManagerProvider.get());
  }

  public static AccountSettingsViewModel_Factory create(
      Provider<AuthRepository> authRepositoryProvider,
      Provider<InvoiceRepository> invoiceRepositoryProvider,
      Provider<DataStoreManager> dataStoreManagerProvider) {
    return new AccountSettingsViewModel_Factory(authRepositoryProvider, invoiceRepositoryProvider, dataStoreManagerProvider);
  }

  public static AccountSettingsViewModel newInstance(AuthRepository authRepository,
      InvoiceRepository invoiceRepository, DataStoreManager dataStoreManager) {
    return new AccountSettingsViewModel(authRepository, invoiceRepository, dataStoreManager);
  }
}
