@extends('site.layout')

@section('content')
<div class="section" style="text-align:center;padding:60px 0;">
    <div class="card-icon purple" style="margin:0 auto 20px;width:64px;height:64px;border-radius:16px;"><i data-lucide="lock"></i></div>
    <h2>Admin Dashboard</h2>
    <p style="max-width:420px;margin:8px auto 24px;">Access the TenantPro admin console to manage properties, tenants, invoices, and maintenance workflows.</p>
    <a href="/admin/login" class="btn btn-primary">Login to Dashboard →</a>
</div>
@endsection
