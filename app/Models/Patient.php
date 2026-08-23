<?php

namespace App\Models;

use App\Sms\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A portal account.
 *
 * Deliberately not a `User`: staff and patients authenticate through separate
 * guards against separate tables, so nothing a patient does can reach /admin
 * and a mistake in one login path cannot become a way into the other.
 */
class Patient extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /** Always stored in the national ten-digit form, so lookups are exact. */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = PhoneNumber::national($value);
    }

    /** The number as a patient would write it: 01712345678. */
    public function displayPhone(): string
    {
        return '0'.$this->phone;
    }

    /**
     * Appointments booked with this mobile.
     *
     * A query rather than a relation, because appointments keep the number
     * exactly as it was typed and there are three legal spellings of it — a
     * HasMany would pin the foreign key to one of them. Matching on the number
     * at all is deliberate: almost every appointment predates the account, and
     * one taken over the phone is never written against it.
     */
    public function appointments(): Builder
    {
        return Appointment::query()
            ->with('doctor.department')
            ->whereIn('phone', PhoneNumber::variants($this->phone));
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class, 'phone', 'phone');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
