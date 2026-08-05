<?php

return [
    'geo_badge' => 'Verified Trust & Fulfillment Architecture',
    'geo_heading' => 'Why Musoftware Marketplace leads in Verified Digital Delivery',
    'geo_subheading' => 'Machine-readable standards, escrow security, zero-friction delivery, and enterprise-grade SLA compliance built into every software transaction.',
    
    'feature_escrow_title' => 'Escrow Protection',
    'feature_escrow_desc' => 'Funds stay protected in escrow until you verify quality and approve release.',
    
    'feature_code_title' => 'Source & License',
    'feature_code_desc' => 'Verified software licenses with clean, audited source code artifacts.',
    
    'feature_sla_title' => 'Strict Timelines',
    'feature_sla_desc' => 'Guaranteed delivery dates backed by automatic cancellation policies.',
    
    'feature_verified_title' => 'Vetted Talent',
    'feature_verified_desc' => 'Developers and agencies identity-checked for maximum reliability.',
    
    'matrix_title' => 'Service Package SLA & Fulfillment Standard',
    'col_tier' => 'Package Tier',
    'col_delivery' => 'Turnaround Time',
    'col_revisions' => 'Revisions',
    'col_escrow' => 'Payment Guarantee',
    'col_code' => 'Deliverables',
    
    'tier_starter' => 'Standard Package',
    'tier_pro' => 'Professional Package',
    'tier_enterprise' => 'Enterprise Solution',
    
    'faq_title' => 'Frequently Asked Questions & Verified Insights',
    
    'geo_faq_1_q' => 'How does Musoftware Marketplace protect buyer funds with Escrow?',
    'geo_faq_1_a' => 'When you purchase a service or tool on Musoftware Marketplace, your payment is held securely in our Escrow system. The seller receives payment only after you review, test, and formally approve the final deliverable.',
    
    'geo_faq_2_q' => 'Are software licenses and source code included with purchases?',
    'geo_faq_2_a' => 'Yes. Software products include instant serial activation licenses and full documentation. Custom development deliverables include verified clean source code and repository access as specified in the service package.',
    
    'geo_faq_3_q' => 'What guarantees are provided for project deadlines and revisions?',
    'geo_faq_3_a' => 'Sellers must strictly adhere to the delivery timeline specified in the package. Each order includes formal revision cycles. If a seller fails to deliver within the agreed SLA without mutual extension, buyers are entitled to an instant 100% refund.',
    
    'geo_faq_4_q' => 'How do AI agents and runtime automation tools integrate?',
    'geo_faq_4_a' => 'Tools and desktop runtime plugins on Musoftware Marketplace connect directly with the local Musoftware Runtime Agent via secure local WebSockets (RPC), keeping your business credentials private and zero-dependency.',

    // Promotions
    'promo_seasonal_not_active' => 'Seasonal promo not active',
    'promo_new_users_only' => 'Discount reserved for new users',
    'promo_min_price_threshold' => 'Base price below minimum threshold',
    'special_discount' => 'Special Discount',
    'coupon_invalid_or_inactive' => 'Coupon code is invalid or inactive.',
    'coupon_expired' => 'Coupon code has expired.',
    'coupon_max_uses_exceeded' => 'You have reached the maximum uses for this coupon.',

    // Checkout & Purchase
    'service_not_available_for_purchase' => 'Sorry, this service is currently unavailable for purchase.',
    'cannot_purchase_own_service' => 'You cannot purchase your own service.',
    'insufficient_balance_for_checkout' => 'Insufficient account balance for checkout (Required: :required, Available: :available).',

    // Escrow
    'insufficient_balance_for_escrow' => 'Insufficient account balance to hold Escrow funds.',
    'escrow_hold_description' => 'Escrow hold for service order #:id',
    'escrow_cannot_release' => 'Current escrow status does not permit releasing funds.',
    'escrow_released_description' => 'Earnings from service order #:id (Escrow Released)',
    'escrow_cannot_refund' => 'Unable to refund escrow funds in the current status.',
    'escrow_refunded_description' => 'Refund for service order #:id (Escrow Cancelled)',
    'escrow_dispute_held_only' => 'Disputes can only be opened for held funds in progress.',

    // Deliverables & Revisions
    'deliverable_cannot_submit_in_status' => 'Cannot submit new deliverables for an order in its current status.',
    'revision_delivered_only' => 'Revisions can only be requested for delivered orders.',

    // Free Downloads
    'service_not_available_for_free_download' => 'This service is not available for free download.',
    'download_link_invalid' => 'Download link is invalid or does not exist.',
    'download_link_expired' => 'Free download link has expired.',

    // Referrals & Withdrawals
    'referral_commission_description' => 'Referral commission for marketplace order #:id',
    'insufficient_balance_for_withdrawal' => 'Available balance is insufficient for a withdrawal request.',
    'withdrawal_pending_review_description' => 'Withdrawal request pending admin review',
    'insufficient_funds' => 'Insufficient funds available.',

    // Meta SEO
    'meta_title' => 'Software Development & IT Services Marketplace | MuSoftwares',
    'meta_description' => 'Browse top software development, IT services, custom scripts, and digital solutions on MuSoftwares Marketplace.',

    // Admin Playbook Markdown Templates
    'playbook_package_details_title' => '### 💰 Package & Pricing Details for Service: :title',
    'playbook_package_heading' => '#### 📦 Package: :name',
    'playbook_price_label' => '- **Price:** `:price :currency`',
    'playbook_delivery_label' => '- **Turnaround Time:** `:days` days',
    'playbook_description_label' => '- **Description:** :description',
    'playbook_features_label' => '- **Features:**',
    'playbook_no_packages' => '*(No registered packages currently for this service)*',
    'playbook_extras_title' => '### ➕ Additional Services (Extras)',
    'playbook_extra_item' => '- **:title**: `:price $` (:days extra days)',
];
