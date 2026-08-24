<?php

/*
 | Bangla — notification emails.
 |
 | Mirrors lang/en/mail.php key for key. Field labels come from
 | `appointment.confirmed.*`, which is already translated.
 |
 | Reviewed by a native speaker: not yet.
 */

return [
    'greeting' => 'প্রিয় :name,',
    'signoff' => 'অ্যাপয়েন্টমেন্ট ডেস্ক',
    'auto_note' => 'এই বার্তাটি স্বয়ংক্রিয়ভাবে পাঠানো হয়েছে — এর উত্তর দেবেন না। যেকোনো প্রয়োজনে :hotline নম্বরে কল করুন।',
    'emergency_note' => 'উপসর্গ তীব্র হলে অ্যাপয়েন্টমেন্টের দিনের জন্য অপেক্ষা করবেন না। যেকোনো সময় জরুরি বিভাগে চলে আসুন, অথবা অ্যাম্বুলেন্সের জন্য :number নম্বরে কল করুন।',

    'patient_booked' => [
        'subject' => 'অ্যাপয়েন্টমেন্ট :reference — :hospital',
        'preheader' => 'আপনার বুকিং রেফারেন্স :reference।',
        'heading_pending' => 'আপনার বুকিং আমরা পেয়েছি',
        'heading_confirmed' => 'আপনার অ্যাপয়েন্টমেন্ট নিশ্চিত হয়েছে',
        'intro_pending' => 'আমাদের অ্যাপয়েন্টমেন্ট ডেস্ক শীঘ্রই :phone নম্বরে ফোন বা এসএমএসের মাধ্যমে সময়টি নিশ্চিত করবে। ততক্ষণ আপনার আর কিছু করার নেই।',
        'intro_confirmed' => 'আপনার সময়টি নির্ধারিত রয়েছে। রেজিস্ট্রেশন আগেই শেষ করতে অনুগ্রহ করে ১৫ মিনিট আগে আসুন।',
        'cta' => 'আপনার অ্যাপয়েন্টমেন্ট দেখুন',
        'change_body' => 'সময় পরিবর্তন বা বাতিল করতে :number নম্বরে কল করে রেফারেন্স নম্বরটি বলুন।',
    ],

    'patient_status' => [
        'subject_confirmed' => 'নিশ্চিত — অ্যাপয়েন্টমেন্ট :reference',
        'subject_cancelled' => 'বাতিল — অ্যাপয়েন্টমেন্ট :reference',
        'preheader_confirmed' => ':date তারিখের সময়টি নির্ধারিত রয়েছে।',
        'preheader_cancelled' => ':date তারিখের অ্যাপয়েন্টমেন্টটি আর হচ্ছে না।',
        'heading_confirmed' => 'আপনার অ্যাপয়েন্টমেন্ট নিশ্চিত হয়েছে',
        'heading_cancelled' => 'আপনার অ্যাপয়েন্টমেন্ট বাতিল করা হয়েছে',
        'intro_confirmed' => ':doctor এর সাথে আপনার সময়টি নির্ধারিত রয়েছে। রেজিস্ট্রেশন আগেই শেষ করতে অনুগ্রহ করে ১৫ মিনিট আগে আসুন।',
        'intro_cancelled' => ':date তারিখে :doctor এর সাথে আপনার অ্যাপয়েন্টমেন্টটি আর হচ্ছে না। কোনো টাকা কাটা হয়নি।',
        'cta_rebook' => 'নতুন অ্যাপয়েন্টমেন্ট নিন',
        'rebook_body' => 'অন্য সময়ে বুক করতে :number নম্বরে কল করুন অথবা ওয়েবসাইট ব্যবহার করুন।',
    ],

    'reminder' => [
        'subject' => 'আগামীকাল — অ্যাপয়েন্টমেন্ট :reference',
        'preheader' => ':doctor, আগামীকাল :time।',
        'heading' => 'আপনার অ্যাপয়েন্টমেন্ট আগামীকাল',
        'intro' => 'মনে করিয়ে দিচ্ছি, আগামীকাল আপনি :doctor এর কাছে যাচ্ছেন। রেজিস্ট্রেশন আগেই শেষ করতে অনুগ্রহ করে ১৫ মিনিট আগে আসুন।',
        'bring_title' => 'সঙ্গে যা আনবেন',
    ],

    'staff_alert' => [
        'subject' => 'নতুন বুকিং :reference — :doctor',
        'preheader' => ':patient, :date, :time।',
        'heading' => 'নতুন একটি অ্যাপয়েন্টমেন্ট এসেছে',
        'intro' => ':patient ওয়েবসাইট থেকে বুক করেছেন এবং নিশ্চিতকরণের অপেক্ষায় আছেন।',
        'cta' => 'স্টাফ প্যানেলে খুলুন',
    ],

    'payment_received' => [
        'subject' => 'পরিশোধ নিশ্চিত — :hospital',
        'preheader' => 'আপনার পরিশোধ নথিভুক্ত করা হয়েছে।',
        'heading' => 'পরিশোধ নিশ্চিত',
        'intro' => 'ধন্যবাদ — :title এর টাকা :amount পরিশোধ নথিভুক্ত হয়েছে।',
        'amount_label' => 'পরিমাণ',
        'date_label' => 'পরিশোধের তারিখ',
        'closing' => 'কোনো প্রশ্ন থাকলে অনুগ্রহ করে কল করুন।',
    ],

    'labels' => [
        'phone' => 'ফোন',
        'email' => 'ইমেইল',
        'age' => 'বয়স',
        'visit_type' => 'ভিজিটের ধরন',
        'notes' => 'রোগীর নোট',
        'booked_at' => 'বুক হয়েছে',
    ],
];
