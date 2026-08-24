<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PatientDocument;
use App\Models\PaymentTransaction;
use App\Payments\SslcommerzGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly SslcommerzGateway $gateway) {}

    public function initiate(PatientDocument $document): RedirectResponse
    {
        abort_unless($document->phone === auth('patient')->user()->phone, 404);
        abort_if($document->category !== 'bill' || $document->payment_status === 'paid', 404);

        $transaction = PaymentTransaction::create([
            'patient_document_id' => $document->id,
            'tran_id' => Str::ulid(),
            'amount' => $document->amount,
            'status' => 'initiated',
        ]);

        $gatewayUrl = $this->gateway->initiate([
            'total_amount' => $document->amount,
            'currency' => 'BDT',
            'tran_id' => $transaction->tran_id,
            'success_url' => route('payments.success', $transaction),
            'fail_url' => route('payments.fail', $transaction),
            'cancel_url' => route('payments.cancel', $transaction),
            'ipn_url' => route('payments.ipn'),
            'cus_name' => $document->patient?->name ?? 'Patient',
            'cus_phone' => $document->phone,
            'product_category' => 'medical',
        ]);

        return redirect()->away($gatewayUrl);
    }

    public function success(PaymentTransaction $transaction): View
    {
        return view('portal.payments.result', ['transaction' => $transaction]);
    }

    public function fail(PaymentTransaction $transaction): View
    {
        return view('portal.payments.result', ['transaction' => $transaction]);
    }

    public function cancel(PaymentTransaction $transaction): View
    {
        return view('portal.payments.result', ['transaction' => $transaction]);
    }
}
