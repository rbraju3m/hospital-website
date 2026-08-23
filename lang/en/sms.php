<?php

/*
|--------------------------------------------------------------------------
| SMS templates
|--------------------------------------------------------------------------
| Keep these short. Operators bill per segment: 160 characters in Latin, but
| only 70 in Bangla, because any Bangla character forces the whole message
| into UCS-2. A sentence added here is a recurring cost on every booking.
|
| SmsTemplateLengthTest renders each of these with representative values and
| fails if one grows past config('sms.segment_warning') segments.
*/

return [
    'booked_pending' => ':hospital: booking :reference. :doctor, :date :time. We will confirm shortly. Call :phone to change.',
    'booked_confirmed' => ':hospital: :reference confirmed. :doctor, :date :time. Please arrive 15 min early. Call :phone to change.',
    'confirmed' => ':hospital: :reference confirmed. :doctor, :date :time. Please arrive 15 min early.',
    'cancelled' => ':hospital: booking :reference on :date is cancelled. Call :phone to book another time.',
    'desk_alert' => 'New booking :reference. :patient, :contact. :doctor, :date :time.',
];
