<?php

namespace App\Services;

use App\Models\PatientPasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Six-digit reset codes, sent by SMS.
 *
 * Email is optional on a portal account — most patients never give one — so a
 * code to the registered mobile is the only recovery route that reaches
 * everybody. That makes this the weakest point in the portal's security, and
 * it is built accordingly: codes are hashed at rest, single use, short lived,
 * and give up after a handful of wrong guesses.
 */
class PasswordResetCodes
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /** Issues a code and returns it in the clear, once, for sending. */
    public function issue(string $nationalPhone): string
    {
        // Any earlier code stops working the moment a new one is asked for.
        PatientPasswordReset::where('phone', $nationalPhone)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PatientPasswordReset::create([
            'phone' => $nationalPhone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $code;
    }

    /**
     * Consume a code. True only once, and only for the right number.
     *
     * A wrong guess is counted; after MAX_ATTEMPTS the code is burned rather
     * than left to be brute-forced through the remaining minutes.
     */
    public function consume(string $nationalPhone, string $code): bool
    {
        $reset = PatientPasswordReset::where('phone', $nationalPhone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $reset) {
            return false;
        }

        if (! Hash::check($code, $reset->code_hash)) {
            $reset->increment('attempts');

            if ($reset->attempts >= self::MAX_ATTEMPTS) {
                $reset->forceFill(['used_at' => now()])->save();
            }

            return false;
        }

        $reset->forceFill(['used_at' => now()])->save();

        return true;
    }

    /** Housekeeping for the scheduler: nothing here is worth keeping. */
    public function prune(): int
    {
        return PatientPasswordReset::where('created_at', '<', now()->subDay())->delete();
    }

    /** A code is only ever compared, never displayed back. */
    public function masked(string $code): string
    {
        return Str::repeat('•', strlen($code));
    }
}
