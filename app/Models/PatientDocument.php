<?php

namespace App\Models;

use App\Sms\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDocument extends Model
{
    protected $guarded = [];

    public const CATEGORIES = ['report', 'prescription', 'bill'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'downloaded_at' => 'datetime',
        ];
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = PhoneNumber::national($value);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** The account this document belongs to, if the patient has registered. */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'phone', 'phone');
    }

    public function scopeForPhone(Builder $query, ?string $phone): Builder
    {
        return $query->where('phone', PhoneNumber::national($phone));
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('issued_at')->orderByDesc('id');
    }

    public function readableSize(): string
    {
        $kb = $this->size / 1024;

        return $kb < 1024
            ? number_format($kb).' KB'
            : number_format($kb / 1024, 1).' MB';
    }
}
