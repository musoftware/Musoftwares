<?php

// The site's Telegram bot: what a linked account sees there.
return [
    'welcome' => 'Hello :name, your Musoftware account is linked here. Use the buttons below to see your invoices and projects, or write any question and the AI assistant answers.',
    'not_linked' => 'Welcome to Musoftware. To see your invoices and projects here, link your account from the settings page: :url',
    'btn_approvals' => 'Approvals',
    'btn_invoices' => 'My invoices',
    'btn_projects' => 'My projects',
    'btn_support' => 'Ask the assistant',
    'support_prompt' => 'Write your question here and the AI assistant will answer.',
    'invoices_title' => 'Your latest invoices:',
    'invoices_none' => 'There are no invoices on your account.',
    'invoice_line' => 'Invoice #:id · :amount · :status',
    'invoice_status' => ['paid' => 'Paid', 'unpaid' => 'Unpaid', 'partially_paid' => 'Partly paid', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'],
    'pay' => 'Pay invoice #:id',
    'all_invoices' => 'All invoices',
    'projects_title' => 'Your projects:',
    'projects_none' => 'No projects yet. You can start one here: :url',
    'project_line' => ':title · :status',
    'project_status' => ['chatting' => 'Still in the chat', 'quoted' => 'Priced, waiting for payment', 'queued' => 'Paid, in line', 'designing' => 'Being designed', 'building' => 'Being built now', 'ready' => 'Ready', 'failed' => 'Stopped, the team is on it'],
    'untitled' => 'Untitled project',
    'open' => 'Open: :title',
];
