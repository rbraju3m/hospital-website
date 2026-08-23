<?php

namespace App\Sms;

/**
 * Bangladeshi mobile numbers, as gateways want them.
 *
 * The booking form accepts /^(?:\+?88)?01[3-9]\d{8}$/, so a stored number may
 * be 01712345678, 8801712345678 or +8801712345678. All three normalise to the
 * same thing.
 *
 * Note the two ways of writing the same number: the country code is 880 and
 * the subscriber number is ten digits (1712345678), but the form's regex reads
 * it as 88 followed by the national 01712345678. Both produce the same
 * digits — this class works in the first form, because that is what an
 * international gateway expects.
 */
class PhoneNumber
{
    /** Country dialling code, digits only. */
    private const KNOWN_PREFIXES = ['880', '88'];

    /**
     * The ten-digit subscriber number: no country code, no trunk zero.
     * 01712345678 and +8801712345678 both become 1712345678.
     */
    public static function national(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if (blank($digits)) {
            return null;
        }

        // Only strip a country code from something long enough to have one —
        // a national number never reaches 12 digits.
        foreach (self::KNOWN_PREFIXES as $prefix) {
            if (strlen($digits) >= 12 && str_starts_with($digits, $prefix)) {
                $digits = substr($digits, strlen($prefix));
                break;
            }
        }

        return ltrim($digits, '0');
    }

    public static function forGateway(?string $number): ?string
    {
        $national = self::national($number);

        if (blank($national)) {
            return null;
        }

        $normalised = config('sms.country_code', '880').$national;

        return config('sms.plus_prefix') ? '+'.$normalised : $normalised;
    }

    /**
     * Every form the same number may already be stored in.
     *
     * Appointments and contact messages keep the number exactly as it was
     * typed, and the validation regex allows three spellings — so matching a
     * portal account to its appointments means comparing against all of them.
     * Coupled to App\Support\Rules::BD_MOBILE: widen that and widen this.
     *
     * @return list<string>
     */
    public static function variants(?string $number): array
    {
        $national = self::national($number);

        if (blank($national)) {
            return [];
        }

        return ['0'.$national, '88'.'0'.$national, '+88'.'0'.$national];
    }

    /**
     * Whether this number can receive an SMS at all.
     *
     * Bangladeshi mobiles are 01[3-9] nationally. The hospital's published
     * lines are 96xx corporate numbers, which look valid and cannot receive
     * one — worth rejecting here rather than failing once per booking.
     */
    public static function isMobile(?string $number): bool
    {
        return (bool) preg_match('/^1[3-9]\d{8}$/', (string) self::national($number));
    }
}
