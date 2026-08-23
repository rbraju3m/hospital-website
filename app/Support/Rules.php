<?php

namespace App\Support;

/**
 * Validation fragments shared across form requests.
 *
 * The mobile pattern in particular was copied into three requests and is about
 * to be needed by a fourth; one definition means a change to what counts as a
 * valid number cannot half-apply.
 */
class Rules
{
    /**
     * A Bangladeshi mobile number: 01[3-9] plus eight digits, optionally
     * carrying the 88 country code with or without a plus.
     *
     * Kept in step with App\Sms\PhoneNumber, which normalises the same
     * three forms for the SMS gateway.
     */
    public const BD_MOBILE = 'regex:/^(?:\+?88)?01[3-9]\d{8}$/';
}
