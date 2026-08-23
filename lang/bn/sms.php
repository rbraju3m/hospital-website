<?php

/*
 | Bangla — SMS templates.
 |
 | Every one of these costs two to three segments where the English costs one,
 | because Bangla forces UCS-2 encoding (70 characters per segment, not 160).
 | Shortening them is the single most effective thing anyone can do about the
 | SMS bill.
 |
 | Reviewed by a native speaker: not yet.
 */

return [
    'booked_pending' => ':hospital: বুকিং :reference। :doctor, :date :time। শীঘ্রই নিশ্চিত করা হবে। পরিবর্তনে :phone।',
    'booked_confirmed' => ':hospital: :reference নিশ্চিত। :doctor, :date :time। ১৫ মিনিট আগে আসুন। পরিবর্তনে :phone।',
    'confirmed' => ':hospital: :reference নিশ্চিত। :doctor, :date :time। ১৫ মিনিট আগে আসুন।',
    // Trimmed from a three-segment message to two: the word for "tomorrow"
    // already does the work "a reminder that…" was doing.
    'reminder' => ':hospital: :reference — আগামীকাল :date :time, :doctor। ১৫ মিনিট আগে আসুন। পরিবর্তনে :phone।',
    'cancelled' => ':hospital: :date তারিখের বুকিং :reference বাতিল। নতুন সময়ের জন্য :phone।',
    'desk_alert' => 'নতুন বুকিং :reference। :patient, :contact। :doctor, :date :time।',
];
