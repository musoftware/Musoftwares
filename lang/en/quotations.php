<?php

return [
    // Navigation & Titles
    'title' => 'Quotations',
    'admin_title' => 'Quotations - Admin',
    'management_title' => 'Quotations & Proposals',
    'management_desc' => 'Create and manage general quotation proposals with automatic 50% deposit payment links and external cost estimates.',
    'create_new' => 'Create New Quotation',
    'edit_title' => 'Edit Quotation: :number',
    'show_title' => 'Quotation: :title - Admin',
    'public_link' => 'Public Proposal Link',
    'shortlink' => 'Short Link',
    'view_details' => 'View Details',
    'duplicate' => 'Duplicate',
    'delete' => 'Delete',
    'save' => 'Save Quotation',
    'save_and_generate' => 'Save & Generate Shortlink',
    'save_changes' => 'Save Changes',
    'cancel' => 'Cancel',
    'back_to_list' => 'Back to Quotations',
    'back' => 'Back',

    // Statuses
    'status_all' => 'All Statuses',
    'status_active' => 'Active',
    'status_draft' => 'Draft',
    'status_archived' => 'Archived',
    'active_badge' => 'Active & Available to Clients',

    // Currencies
    'currency_all' => 'All Currencies',
    'currency_label' => 'Quotation Currency',

    // KPIs & Metrics
    'total_quotations' => 'Total Quotations',
    'active_quotations' => 'Active Quotations',
    'active_quotations_count' => ':count Active and ready to send',
    'total_views' => 'Quotation Views',
    'total_views_desc' => 'Total client visits to public proposal links',
    'paid_orders' => 'Paid Proposals (50%)',
    'paid_orders_desc' => 'Clients who paid the 50% deposit to start work',
    'total_collected' => 'Collected Revenue',
    'total_collected_desc' => 'Total 50% deposit revenue collected',

    // Filters & Search
    'search_placeholder' => 'Search by title, number, or keyword...',
    'search_button' => 'Search',
    'all_records' => 'Total: :count quotations',

    // Table & Columns
    'col_quotation' => 'Quotation',
    'col_development_total' => 'Development Scope',
    'col_deposit_50' => '50% Deposit',
    'col_indicative_total' => 'Indicative Costs',
    'col_grand_total' => 'Grand Total',
    'col_views' => 'Views',
    'col_orders' => 'Paid / Orders',
    'col_status' => 'Status',
    'col_created' => 'Created At',
    'col_actions' => 'Actions',
    'empty_state_title' => 'No quotations found',
    'empty_state_desc' => 'Create your first proposal to share with prospective clients.',

    // Forms
    'section_basic' => 'Basic Information & Currency',
    'section_basic_desc' => 'Define quotation title, currency, deposit percentage, and validity.',
    'field_title' => 'Quotation Title',
    'field_title_placeholder' => 'e.g. Full-Stack E-Commerce Platform & Mobile App Development',
    'field_deposit_percentage' => 'Deposit Percentage (%)',
    'field_deposit_percentage_desc' => 'Percentage required from our development scope to initiate the project (default 50%).',
    'field_valid_until' => 'Valid Until (Optional)',
    'field_status' => 'Proposal Status',
    'field_notes' => 'Internal Notes',
    'field_notes_placeholder' => 'Internal notes only visible to staff...',

    // Items Manager
    'section_items' => 'Itemized Pricing & Scope',
    'section_items_desc' => 'Deposit is calculated exclusively on our development & programming scope.',
    'tab_our_work' => 'Development & Programming Scope',
    'tab_indicative_cost' => 'Indicative Third-Party Costs',
    'add_our_work_item' => 'Add Development Item',
    'add_indicative_item' => 'Add Third-Party Item',
    'item_title' => 'Item / Feature Title',
    'item_title_placeholder' => 'e.g. Admin Dashboard & Invoicing Engine',
    'item_price' => 'Price',
    'item_qty' => 'Quantity',
    'item_total' => 'Total',
    'item_description' => 'Detailed description & deliverables (Optional)...',
    'external_link' => 'Provider Outbound Link',
    'external_link_placeholder' => 'https://hetzner.com or https://hostinger.com',
    'link_label' => 'Button / Provider Label',
    'link_label_placeholder' => 'e.g. Order Hosting on Hetzner',

    // Financial Box
    'calc_summary_title' => 'Automated Financial Summary',
    'calc_dev_total' => 'Our Development Total',
    'calc_deposit' => 'Deposit Due Now (:pct%)',
    'calc_deposit_sub' => 'Required to initiate development',
    'calc_indicative_total' => 'External Indicative Costs',
    'calc_indicative_sub' => 'Paid directly to third-party providers',
    'calc_grand_total' => 'Estimated Grand Total',

    // Markdown Scope Editor
    'section_scope' => 'Scope of Work, Roadmap & Terms',
    'section_scope_desc' => 'Write comprehensive deliverables, timeline milestones, and conditions with live Markdown preview.',
    'template_saas' => 'SaaS Template',
    'template_ecommerce' => 'Store Template',
    'template_payment' => 'Payment Terms Template',

    // Show & Share Page
    'share_toolbox' => 'Quick Share & Link Tools',
    'copy_shortlink' => 'Copy Short Link',
    'copy_public_link' => 'Copy Direct Link',
    'share_whatsapp' => 'Share via WhatsApp',
    'preview_public' => 'Preview Public Proposal',
    'link_copied' => 'Link copied to clipboard!',
    'wa_copied' => 'WhatsApp message copied to clipboard!',
    'paid_orders_card' => 'Deposit Payments & Client Invoices',
    'client_name' => 'Client Name',
    'client_email' => 'Email',
    'client_phone' => 'Phone / WhatsApp',
    'company' => 'Company',
    'paid_amount' => 'Paid Amount',
    'paid_at' => 'Paid At',
    'view_invoice' => 'View Invoice',
    'no_orders_yet' => 'No client deposit payments recorded for this quotation yet.',

    // Notifications & Confirmations
    'delete_confirm' => 'Are you sure you want to delete quotation ":title"?',
    'deleted_success' => 'Quotation deleted successfully.',
    'duplicated_success' => 'Quotation duplicated successfully.',
    'created_success' => 'Quotation created successfully.',
    'updated_success' => 'Quotation updated successfully.',
];
