<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
            'gateway_response' => 'json',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'tran_id';
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(PatientDocument::class, 'patient_document_id');
    }
}
