<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReceiptPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_receipt_pdf_generates_successfully()
    {
        $tenantRole = Role::create(['name' => 'TENANT', 'description' => 'Tenant']);
        $tenant = User::factory()->create(['role_id' => $tenantRole->id]);

        $landlordRole = Role::create(['name' => 'LANDLORD', 'description' => 'Landlord']);
        $landlord = User::factory()->create(['role_id' => $landlordRole->id]);

        $property = Property::create([
            'name' => 'Sunrise Court',
            'landlord_id' => $landlord->id,
            'address_line' => 'Kilimani, Nairobi',
            'city' => 'Nairobi',
            'water_monthly_fee' => 0,
            'garbage_monthly_fee' => 0,
            'electricity_billing_mode' => 'PREPAID',
            'is_publicly_listed' => true,
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A101',
            'rent_amount' => 25000,
            'status' => 'OCCUPIED',
        ]);

        $invoice = Invoice::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'billing_type' => 'RENT',
            'amount' => 25000,
            'total_amount' => 25000,
            'paid_amount' => 25000,
            'status' => 'PAID',
            'period_month' => 9,
            'period_year' => 2026,
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-05',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 25000,
            'method' => 'MPESA_STK',
            'payment_phone' => '254712345678',
            'mpesa_receipt' => 'QA12BC34DE',
            'status' => 'SUCCESSFUL',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($tenant)->get("/api/payments/{$payment->id}/receipt-pdf");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }
}
