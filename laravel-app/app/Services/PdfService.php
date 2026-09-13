<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PropertyInspection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    public function generatePaymentReceipt(Payment $payment): Response
    {
        $payment->loadMissing(['invoice.tenant', 'invoice.unit.property.landlord']);

        $pdf = Pdf::loadView('pdf.payment-receipt', compact('payment'))
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $filename = 'Starmax-Receipt-' . ($payment->mpesa_receipt ?? substr($payment->id, 0, 8)) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function generateInvoiceStatement(Invoice $invoice): Response
    {
        $invoice->loadMissing(['tenant', 'unit.property.landlord', 'payments']);

        $pdf = Pdf::loadView('pdf.invoice-statement', compact('invoice'))
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $filename = 'Starmax-Invoice-' . substr($invoice->id, 0, 8) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function generateInspectionCertificate(PropertyInspection $inspection): Response
    {
        $inspection->loadMissing(['property.landlord', 'unit', 'tenant']);

        $pdf = Pdf::loadView('pdf.inspection-certificate', compact('inspection'))
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $filename = 'Starmax-Inspection-' . substr($inspection->id, 0, 8) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function generateTrustScoreCertificate(\App\Models\User $tenantUser, array $trustData): Response
    {
        $tenantUser->loadMissing(['role']);

        $pdf = Pdf::loadView('pdf.trust-certificate', compact('tenantUser', 'trustData'))
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $filename = 'Starmax-Trust-Certificate-' . substr($tenantUser->id, 0, 8) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function generateLeaseAgreement(\App\Models\Tenant $tenant): Response
    {
        $tenant->loadMissing(['user', 'unit.property.landlord']);

        $pdf = Pdf::loadView('pdf.lease-agreement', compact('tenant'))
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $filename = 'Starmax-Lease-Agreement-' . ($tenant->unit?->unit_number ?? 'Unit') . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
