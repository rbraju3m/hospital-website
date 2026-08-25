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

    /**
     * Somewhere a button on the site may point.
     *
     * The same allowlist the panel's markup editor applies to `[text](link)`:
     * http(s), mailto:, tel:, root-relative and an in-page anchor. Anything
     * else — `javascript:` above all — is not a destination, it is a way to run
     * something in a visitor's browser from a field a staff member filled in.
     */
    public const LINK = 'regex:/^(?:https?:\/\/|mailto:|tel:|\/|#)[^\s<>"]*$/i';
}
