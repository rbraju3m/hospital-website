<?php

/*
|--------------------------------------------------------------------------
| SMS
|--------------------------------------------------------------------------
| Phone is the only contact detail the booking form requires, so this is the
| channel that reaches every patient. Email reaches the minority who gave an
| address.
|
| The `http` driver is deliberately generic: most Bangladeshi gateways (Alpha
| SMS, BulkSMSBD, MIMSMS, Elitbuzz, Reve) are one GET or POST carrying an API
| key, a number and the text, so switching provider is an .env change rather
| than a new class. Anything stranger than that wants its own driver.
*/

return [

    'default' => env('SMS_DRIVER', 'log'),

    /*
    | The branded sender ID ("masking") the gateway sends as. Most Bangladeshi
    | providers require this to be pre-registered with the operators.
    */
    'sender' => env('SMS_SENDER'),

    /*
    | Numbers are stored as the patient typed them — 01712345678 or
    | +8801712345678. They leave here normalised to 8801712345678, with the
    | leading + only if the gateway wants one.
    */
    'country_code' => env('SMS_COUNTRY_CODE', '880'),
    'plus_prefix' => (bool) env('SMS_PLUS_PREFIX', false),

    /*
    | A Bangla SMS is UCS-2: 70 characters in one segment against 160 for
    | Latin, and every segment is billed. Messages over this are still sent —
    | this only decides when to leave a warning in the log, and it is what the
    | template length test asserts against.
    */
    'segment_warning' => (int) env('SMS_SEGMENT_WARNING', 3),

    'drivers' => [

        'log' => [
            'channel' => env('SMS_LOG_CHANNEL'),
        ],

        // Named `discard`, not `null`: dotenv reads "null" as PHP null.
        'discard' => [],

        'http' => [
            'url' => env('SMS_URL'),
            'method' => env('SMS_METHOD', 'GET'),

            /*
            | Comma-separated `name=value` pairs describing what this gateway
            | calls its parameters. Four tokens are substituted:
            |
            |   :key     SMS_KEY          :to     the normalised number
            |   :text    the message      :sender SMS_SENDER
            |
            | e.g. SMS_PARAMS="api_key=:key,to=:to,msg=:text,sender_id=:sender"
            */
            'params' => env('SMS_PARAMS', 'api_key=:key,to=:to,msg=:text'),

            // Same syntax, for gateways that authenticate with a header.
            'headers' => env('SMS_HEADERS'),

            'key' => env('SMS_KEY'),

            // Send the parameters as a JSON body rather than a form.
            'json' => (bool) env('SMS_JSON', false),

            /*
            | Gateways here routinely answer 200 OK with an error in the body.
            | When set, this string has to appear in the response for the send
            | to count as successful.
            */
            'success' => env('SMS_SUCCESS'),

            'timeout' => (int) env('SMS_TIMEOUT', 10),
        ],
    ],
];
