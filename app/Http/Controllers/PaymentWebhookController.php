<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Payments\SslcommerzGateway;
use App\Services\PaymentNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly SslcommerzGateway $gateway,
        private readonly PaymentNotifier $notifier,
    ) {}

    public function handle(Request $request): Response
    {
        $tranId = $request->input('tran_id');

        $transaction = PaymentTransaction::where('tran_id', $tranId)->first();
        if (!$transaction) {
            return response('OK');
        }

        if ($transaction->status === 'validated') {
            return response('OK');
        }

        if ($request->input('status') !== 'VALID' && $request->input('status') !== 'VALIDATED') {
            $transaction->update(['status' => 'failed']);
            return response('OK');
        }

        try {
            $validated = $this->gateway->validate($request->input('val_id'));
        } catch (\Exception $e) {
            Log::warning("SSLCommerz validation failed for tran_id {$tranId}: {$e->getMessage()}");
            throw $e;
        }

        if ($validated['status'] !== 'VALID' || $validated['amount'] != $transaction->amount) {
            Log::warning("SSLCommerz validation mismatch for tran_id {$tranId}: {$validated['status']}, {$validated['amount']} vs {$transaction->amount}");
            $transaction->update(['status' => 'failed']);
            return response('OK');
        }

        DB::transaction(function () use ($transaction, $validated, $request) {
            $transaction->update([
                'status' => 'validated',
                'val_id' => $request->input('val_id'),
                'card_type' => $validated['card_type'] ?? null,
                'gateway_response' => $validated,
                'validated_at' => now(),
            ]);

            $document = $transaction->document;
            $document->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        $this->notifier->paid($transaction->document);

        return response('OK');
    }
}
