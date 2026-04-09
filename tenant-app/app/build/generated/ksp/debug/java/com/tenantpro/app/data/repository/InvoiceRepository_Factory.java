package com.tenantpro.app.data.repository;

import com.tenantpro.app.data.api.ApiService;
import com.tenantpro.app.data.local.dao.InvoiceDao;
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
public final class InvoiceRepository_Factory implements Factory<InvoiceRepository> {
  private final Provider<ApiService> apiProvider;

  private final Provider<InvoiceDao> invoiceDaoProvider;

  public InvoiceRepository_Factory(Provider<ApiService> apiProvider,
      Provider<InvoiceDao> invoiceDaoProvider) {
    this.apiProvider = apiProvider;
    this.invoiceDaoProvider = invoiceDaoProvider;
  }

  @Override
  public InvoiceRepository get() {
    return newInstance(apiProvider.get(), invoiceDaoProvider.get());
  }

  public static InvoiceRepository_Factory create(Provider<ApiService> apiProvider,
      Provider<InvoiceDao> invoiceDaoProvider) {
    return new InvoiceRepository_Factory(apiProvider, invoiceDaoProvider);
  }

  public static InvoiceRepository newInstance(ApiService api, InvoiceDao invoiceDao) {
    return new InvoiceRepository(api, invoiceDao);
  }
}
