<?php

// A client's own system that we license: monthly price and/or a share of its sales (admin pages and the license).
return [
    // shown on the client's system
    'license_due' => 'Your Musoftwares bill is not paid yet. Please pay before :date to keep the system working.',
    'license_locked' => 'This system is stopped because the Musoftwares bill is not paid. Pay the bill, then press "I paid, check again".',

    // invoice lines
    'invoice_monthly' => 'Monthly fee: :system (:period)',
    'invoice_sales' => ':percent% of sales (:total) before :date: :system',

    // messages after actions
    'created' => 'System added. Copy the prompt below into the AI working on the client\'s project.',
    'saved' => 'Saved.',
    'locked_now' => 'The system is locked. It stops at its next check-in.',
    'unlocked_now' => 'The lock is removed.',
    'keys_rotated' => 'New keys made. The old ones stop working now: give the AI the new prompt.',
    'bill_waits_for_rate' => 'System added, but the first bill waits for today\'s exchange rate. It is made automatically within the hour.',

    // states
    'state_active' => 'Working',
    'state_due' => 'Bill due (warning shown)',
    'state_locked' => 'Locked',

    'price_monthly' => ':amount :currency a month',
    'price_percent' => ':percent% of sales',
    'price_free' => 'No charge',
    'never_checked_in' => 'not connected yet',
    'last_check_in' => 'Last check-in',
    'next_bill' => 'next bill :date',

    // fields
    'field_name' => 'System name',
    'field_site_url' => 'Website address (optional)',
    'field_currency' => 'Currency',
    'field_monthly_price' => 'Monthly price',
    'field_monthly_price_hint' => '0 if you only take a share of sales.',
    'field_sales_percent' => 'Share of sales (%)',
    'field_sales_percent_hint' => '0 if you only take a monthly price.',
    'field_grace_days' => 'Days before the lock',
    'field_grace_days_hint' => 'After a bill, the system shows a warning for these days, then locks until paid.',
    'first_bill_note' => 'The first bill is made now. The system stays locked until it is paid. Later bills come every month.',

    // client page section
    'section_title' => 'Client systems',
    'section_hint' => 'The client\'s own systems that you license. Each one works only while its bills are paid.',
    'new' => 'New system',
    'create' => 'Add system',

    // list page
    'index_title' => 'Client systems',
    'index_hint' => 'Every licensed client system. To add one, open the client\'s page.',
    'index_empty' => 'No client systems yet. Open a client\'s page and press "New system".',
    'alert_changed_short' => 'License code was changed',
    'pages' => 'Pages',

    // system page
    'alert_changed_title' => 'The license code on the client\'s side changed (:date)',
    'alert_changed_body' => 'Someone edited the license files in the client\'s project. If you made this change yourself, accept it. If not, lock the system and check the project.',
    'alert_changed_accept' => 'I made this change, accept it',
    'prompt_title' => 'Prompt for the AI',
    'prompt_hint' => 'Paste it into the AI working on the client\'s project. It works for any language or framework.',
    'prompt_copy' => 'Copy prompt',
    'prompt_copied' => 'Copied',
    'prompt_secret_warning' => 'The prompt holds this system\'s secret key. Give it only to your own AI, never to the client.',
    'prompt_show' => 'Show the prompt',
    'price_title' => 'Price',
    'price_hint' => 'In :currency. Changes apply from the next bill.',
    'save' => 'Save',
    'access_title' => 'Access',
    'open_until' => 'Keep it working until',
    'open_until_hint' => 'The system works until this date even with an unpaid bill. Leave empty to remove.',
    'lock' => 'Lock now',
    'unlock' => 'Remove the lock',
    'rotate' => 'Make new keys',
    'rotate_title' => 'Make new keys?',
    'rotate_body' => 'The client\'s system stops working until the AI applies the new prompt.',
    'bills_title' => 'Bills',
    'bills_hint' => 'Amounts in :currency. Each bill has an invoice in the client\'s account.',
    'bills_empty' => 'No bills yet.',
    'bill_line' => 'monthly :monthly + :fee (:percent% of :total)',
    'bill_invoice' => 'Invoice #:id: :status',
    'bill_nothing' => 'Nothing to pay',
    'invoice_unpaid' => 'unpaid',
    'invoice_partially_paid' => 'partly paid',
    'invoice_paid' => 'paid',
    'invoice_cancelled' => 'cancelled',
    'sales_title' => 'Reported sales',
    'sales_hint' => 'The last 50 sales the client\'s system sent.',
    'sales_empty' => 'No sales reported yet.',
    'sale_paid' => 'paid',
    'sale_refunded' => 'refunded',
    'sale_billed' => 'billed',
];
