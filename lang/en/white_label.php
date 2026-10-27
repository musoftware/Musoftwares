<?php

// White label: partners sell our programs under their own name. /white-label (offer), /white-label/settings (partner), /shop/{partner} (their store).
return [
    'title' => 'White label',
    'badge' => 'For agencies and resellers',
    'headline' => 'Sell our programs under your own name',
    'subtitle' => 'Add our programs to your website with one line of code. Your name, your logo, your prices. You earn 30% of every sale, plus everything you add on top of our price.',
    'price' => '1,000 EGP / month',
    'start' => 'Start selling',
    'see_programs' => 'See the programs',
    'banner_title' => 'White label: sell these programs under your own name',
    'banner_text' => 'Add them to your website with one line of code and earn 30% of every sale, plus your markup. 1,000 EGP / month.',
    'banner_action' => 'How it works',

    'steps_title' => 'How it works',
    'steps' => [
        ['title' => 'Subscribe', 'text' => 'Pay 1,000 EGP a month from your wallet. No contract, stop any time.'],
        ['title' => 'Add your brand', 'text' => 'Your name, logo, color and WhatsApp number, and your price for each program.'],
        ['title' => 'Put it on your site', 'text' => 'Paste one line of code in your website, or share your store link.'],
        ['title' => 'Get paid on every sale', 'text' => 'Your customer pays online and the program activates by itself. Your share goes to your wallet.'],
    ],

    'earn_title' => 'What you earn',
    'earn_text' => 'You can never sell under our price. Sell at our price and you keep 30%. Raise the price and you also keep everything above it.',
    'earn_example' => [
        ['label' => 'Our price', 'value' => '1,000'],
        ['label' => 'Your price', 'value' => '1,300'],
        ['label' => '30% of our price', 'value' => '300'],
        ['label' => 'Your markup', 'value' => '300'],
        ['label' => 'You earn', 'value' => '600'],
    ],

    'brand_title' => 'Your brand everywhere your customer looks',
    'brand_points' => [
        ['icon' => 'ri-store-2-line', 'title' => 'Your store', 'text' => 'Your name, logo and color. Our name is not shown.'],
        ['icon' => 'ri-computer-line', 'title' => 'Inside the program', 'text' => 'The program shows your logo and your WhatsApp number to your customer.'],
        ['icon' => 'ri-customer-service-2-line', 'title' => 'Your customers', 'text' => 'Your customers contact you. We support you, you support them.'],
    ],
    'payment_note' => 'Payment is taken by our payment company (Kashier), so the card statement shows the company that takes the payment.',

    'faq_title' => 'Questions',
    'faq' => [
        ['q' => 'Which programs can I sell?', 'a' => 'Every program on our services page. Business systems and services are not included.'],
        ['q' => 'Can I sell for less than your price?', 'a' => 'No. Your price can be the same as ours or higher. If you leave a price empty, our price is used.'],
        ['q' => 'When do I get my money?', 'a' => 'Your share is recorded when the customer pays. It waits 14 days, then moves to your wallet, and you can withdraw it.'],
        ['q' => 'What happens if I stop paying the subscription?', 'a' => 'Your store stops taking new orders. Programs your customers already bought keep working, and show our name again.'],
    ],

    // Partner settings page
    'settings_hint' => 'Sell our programs under your name and earn on every sale.',
    'status_active' => 'Your store is open until :date.',
    'status_inactive' => 'Your store is closed. Subscribe to open it.',
    'subscribe' => 'Subscribe: 1,000 EGP / month',
    'renew' => 'Add one month: 1,000 EGP',
    'wallet' => 'Your wallet: :amount',
    'add_balance' => 'Add balance',
    'not_enough_balance' => 'Your wallet does not have enough balance for one month. Add balance, then try again.',
    'subscribe_failed' => 'We could not take the subscription. Please try again or contact us.',
    'subscribed' => 'Done. Your store is open until :date.',
    'save_brand_first' => 'Save your brand first.',
    'saved' => 'Saved.',

    'brand_section' => 'Your brand',
    'brand_name' => 'Brand name',
    'slug' => 'Store address',
    'slug_hint' => 'Small English letters, numbers and dashes.',
    'logo' => 'Logo',
    'logo_hint' => 'PNG, JPG or WebP, up to 1 MB.',
    'accent_color' => 'Color',
    'support_whatsapp' => 'WhatsApp number for your customers',
    'support_email' => 'Email for your customers',
    'website_url' => 'Your website',
    'save' => 'Save',

    'prices_section' => 'Your prices',
    'prices_hint' => 'Leave a price empty to sell at our price. A price under ours is not accepted.',
    'program' => 'Program',
    'our_price' => 'Our price',
    'your_price' => 'Your price',
    'you_earn' => 'You earn',
    'price_too_low' => 'Your price for :program cannot be under :price.',

    'embed_section' => 'Put it on your website',
    'embed_hint' => 'Copy this code into your website where you want the store to show.',
    'store_link' => 'Your store link',
    'copy' => 'Copy',
    'copied' => 'Copied',
    'open_store' => 'Open your store',

    'sales_section' => 'Your sales',
    'no_sales' => 'No sales yet. Share your store link to get your first one.',
    'date' => 'Date',
    'customer' => 'Customer',
    'paid' => 'Paid',
    'waiting' => 'Waiting for payment',
    'your_share' => 'Your share',

    // Partner store
    'programs' => 'Programs',
    'store_hint' => 'Pay online. The program activates by itself after payment.',
    'buy' => 'Buy',
    'from' => 'from :price',
    'contact' => 'Contact us',
    'back_to_store' => 'Back to the store',
    'activates_itself' => 'After payment the program activates by itself.',
];
