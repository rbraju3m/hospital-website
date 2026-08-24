<?php

namespace App\Payments;

use Illuminate\Support\Facades\Http;

class SslcommerzGateway
{
    public function __construct(private readonly array $config) {}

    public function initiate(array $params): string
    {
        $response = Http::asForm()
            ->timeout($this->config['timeout'])
            ->post("{$this->config['base_url']}/gwprocess/v4/api.php", array_merge($params, [
                'store_id' => $this->config['store_id'],
                'store_passwd' => $this->config['store_password'],
            ]));

        if ($response->failed() || $response->json('status') !== 'SUCCESS') {
            throw new PaymentGatewayException('SSLCommerz init failed: '.$response->json('failedreason', $response->body()));
        }

        return $response->json('GatewayPageURL');
    }

    public function validate(string $valId): array
    {
        $response = Http::timeout($this->config['timeout'])
            ->get("{$this->config['base_url']}/validator/api/validationserverAPI.php", [
                'val_id' => $valId,
                'store_id' => $this->config['store_id'],
                'store_passwd' => $this->config['store_password'],
                'format' => 'json',
            ]);

        if ($response->failed()) {
            throw new PaymentGatewayException("Validation call returned HTTP {$response->status()}.");
        }

        return $response->json();
    }
}
