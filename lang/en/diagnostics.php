<?php

/*
|--------------------------------------------------------------------------
| Diagnostics catalogue
|--------------------------------------------------------------------------
| The published price list. Category slugs are enum-ish, not content, so they
| resolve through category_label('diagnostics', $slug).
*/

return [
    'index' => [
        'meta_title' => 'Diagnostics & Test Prices',
        'meta_description' => 'Laboratory, imaging, cardiac and endoscopy tests at :name, with prices, preparation instructions and report times.',
        'crumb' => 'Diagnostics',
        'eyebrow' => 'Diagnostics',
        'title' => 'Tests and prices',
        'lede' => 'What each test costs, how to prepare for it, and when the report is ready. Prices are what you pay at the counter — there is nothing to add on top.',
        'search_label' => 'Search tests',
        'search_placeholder' => 'Test name or code — try CBC',
        'all_categories' => 'All categories',
        'filter' => 'Filter',
        'reset' => 'Clear',
        'found' => '{0} No tests match|{1} :count test|[2,*] :count tests',
        'found_for' => 'for “:term”',
        'empty' => 'No test matches that search.',
        'empty_hint' => 'Try the test code from your prescription, or call the hotline and read it out.',
        'home_collection' => 'Home collection',
        'home_collection_short' => 'Home',
        'report_in' => 'Report :time',
        'prices_note' => 'Prices last reviewed for the current financial year. A doctor may add or remove tests after seeing you.',
    ],

    'show' => [
        'crumb' => 'Diagnostics',
        'eyebrow' => 'Diagnostic test',
        'code_label' => 'Test code',
        'price_label' => 'Price',
        'price_hint' => 'Payable at the counter',
        'preparation_title' => 'How to prepare',
        'no_preparation' => 'No preparation needed.',
        'sample_title' => 'Sample',
        'report_title' => 'Report ready',
        'home_collection_title' => 'Home collection available',
        'home_collection_body' => 'A phlebotomist can collect this sample at your home. Call :phone to arrange it; a collection charge applies.',
        'related_title' => 'Other :category tests',
        'how_title' => 'How it works',
        'how_1' => 'Come to the diagnostics counter on the ground floor with your prescription, or request the test below and we will call you back.',
        'how_2' => 'Pay at the counter. No appointment is needed for laboratory tests.',
        'how_3' => 'Collect the report, or ask for it to be emailed to you.',
    ],

    'request' => [
        'closed' => 'Test requests through the website are switched off at the moment. Call :phone and the counter will arrange it.',
        'title' => 'Request this test',
        'lede' => 'Leave your number and the diagnostics desk will call you back to arrange it.',
        'name' => 'Your name',
        'phone' => 'Mobile number',
        'email' => 'Email',
        'notes' => 'Anything we should know',
        'notes_placeholder' => 'Preferred day, home collection, a doctor’s instruction…',
        'submit' => 'Request a call back',
        'success' => 'Thank you — the diagnostics desk will call :phone shortly.',
        'subject' => 'Test request: :test',
        'body' => "Requested test: :test (:code)\nListed price: :price",
    ],

    'categories' => [
        'pathology' => 'Laboratory',
        'imaging' => 'Radiology & Imaging',
        'cardiology' => 'Cardiac Diagnostics',
        'endoscopy' => 'Endoscopy',
    ],
];
