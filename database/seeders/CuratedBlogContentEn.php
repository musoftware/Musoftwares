<?php

namespace Database\Seeders;

/**
 * English versions of three of the Arabic articles. Same facts, same limits.
 */
class CuratedBlogContentEn
{
    /** @return array<int, array<string, mixed>> */
    public static function articles(): array
    {
        return [self::websiteCost(), self::pharmacyChecklist(), self::sourceOrHosted()];
    }

    private static function websiteCost(): array
    {
        return [
            'key' => 'website-cost',
            'slug' => 'website-design-cost-egypt-2026-en',
            'days_ago' => 20,
            'title' => 'How Much Does a Company Website Cost in Egypt in 2026?',
            'meta_title' => 'Company Website Cost in Egypt 2026: Numbers Explained',
            'meta_description' => 'Published prices for company websites from Egyptian agencies, what changes the price, and what to get in writing before you pay any agency or freelancer.',
            'excerpt' => 'The asking prices three Egyptian agencies publish for company websites, what drives the difference, and what to check in any offer.',
            'content' => <<<'HTML'
<p>If you are asking how much a company website costs in Egypt, the honest answer is that there is no single number. Prices run from a few thousand pounds to hundreds of thousands, depending on the size of the site and how it is built. The figures below are <strong>asking prices published on agency pages</strong>. They are marketing ranges, not quotes, and not closed deals.</p>

<h2>Published prices from three Egyptian agencies</h2>
<p>We read the price pages of three agencies. All amounts are in Egyptian pounds, and each agency groups sites in its own way:</p>
<table>
<thead><tr><th>Source</th><th>Type of site</th><th>Published range</th></tr></thead>
<tbody>
<tr><td>lightweb2.com (page dated 2 June 2026)</td><td>Ready template with Elementor</td><td>5,000 to 15,000</td></tr>
<tr><td>lightweb2.com</td><td>Professional custom WordPress</td><td>20,000 to 50,000</td></tr>
<tr><td>lightweb2.com</td><td>Advanced, built with Laravel or React</td><td>60,000 to 150,000 or more</td></tr>
<tr><td>web-pioneer.com (undated)</td><td>Brochure site, up to 6 pages</td><td>5,000 to 25,000 (2 to 3 weeks)</td></tr>
<tr><td>web-pioneer.com</td><td>Company site, bilingual, with training</td><td>15,000 to 50,000 (4 to 6 weeks)</td></tr>
<tr><td>web-pioneer.com</td><td>Custom site</td><td>from 70,000</td></tr>
<tr><td>ensignagency.com (page dated 30 December 2025)</td><td>Simple site, 5 to 10 pages</td><td>6,000 to 15,000</td></tr>
<tr><td>ensignagency.com</td><td>Medium</td><td>15,000 to 30,000</td></tr>
<tr><td>ensignagency.com</td><td>Advanced</td><td>30,000 to 50,000 or more</td></tr>
</tbody>
</table>
<p>Two of the three say that hosting and domain are charged separately. Always ask whether a price includes them.</p>

<h2>What really changes the price</h2>
<ul>
<li><strong>Number of pages:</strong> a five-page site is not a fifteen-page site with services, projects and a blog.</li>
<li><strong>Languages:</strong> an Arabic and English site needs a proper right-to-left layout and translated text, which is more work than one language.</li>
<li><strong>Template or custom design:</strong> a template is cheaper and faster; a design from scratch takes more designer time.</li>
<li><strong>Content:</strong> who writes the texts and finds the photos? If it is you, the price drops. If it is the agency, it rises.</li>
<li><strong>Extra features:</strong> booking, online payment, a client area, links to other systems. Each can change the whole project.</li>
<li><strong>Speed and backups:</strong> some agencies include them, others forget them.</li>
<li><strong>Support after delivery:</strong> free fixes for a period, or a monthly subscription?</li>
</ul>

<h2>What should be written in the offer before you pay</h2>
<ol>
<li>The exact number of pages and languages, and what is on each page.</li>
<li>The number of free revision rounds, and the price of changes after that.</li>
<li>The delivery time, and when the clock starts (from payment or from receiving your content).</li>
<li>Who owns the site, the code and the domain after delivery.</li>
<li>The yearly renewal cost of domain and hosting, because it repeats.</li>
<li>Paid extras: themes, plugins, fonts, photos.</li>
<li>The support period after delivery and what it covers.</li>
<li>How you pay: once, or in stages tied to deliveries. One of the three (web-pioneer) says payment is in stages and that code and domain are transferred in the contract.</li>
</ol>

<h2>Our packages at Musoftware</h2>
<p>On the <a href="/services/company-website-design">company website design</a> page we list three fixed-price packages. What follows matches that page:</p>
<ul>
<li><strong>Starter, 9,000 EGP:</strong> up to 5 pages in one language from a template made to fit your brand, a contact form and a WhatsApp button, SSL and basic on-page SEO, 2 revision rounds, about 2 weeks, and 30 days of fixes after handover.</li>
<li><strong>Business, 22,000 EGP:</strong> up to 10 pages in Arabic and English, a blog and an editor you can use, Google Analytics and Search Console setup, one hour of training for your team, 3 to 4 weeks.</li>
<li><strong>Premium, 45,000 EGP:</strong> a custom design, up to 15 pages, Arabic and English, a speed target checked before handover, structured data for search engines, 3 revision rounds, 5 to 6 weeks.</li>
</ul>
<p>Not included: writing your texts, photos and logo, the domain and hosting and their yearly renewal, paid plugins and fonts, and pages beyond the plan. Ownership moves to you after full payment.</p>
<p>These prices sit in the lower to middle part of the published ranges above. That is not a claim that we are the cheapest. If you are not sure what you need, try the <a href="/estimator">cost estimator</a> to find a first range, or message us on WhatsApp.</p>

<h2>What about freelancers?</h2>
<p>On freelance platforms you will find much cheaper offers, starting from token amounts in dollars. That is a different market: the scope is usually smaller, and sometimes there are no source files or clear revision rounds. It does not mean the work is poor, but compare on the same points as above, not on the number alone.</p>

<h2>Quick questions</h2>
<h3>How long does a site take?</h3>
<p>From two weeks to about six, depending on the package. The clock starts when we receive your content and materials.</p>
<h3>Is hosting and the domain included?</h3>
<p>No. They are paid to the hosting provider and renew every year. We write that in the offer.</p>
<h3>Can I edit the texts myself later?</h3>
<p>Yes. We build on WordPress so you can change texts and photos without a developer.</p>
<h3>Are these numbers current?</h3>
<p>They are what was published when this article was written. Some pages are undated, so treat them as a rough guide and ask any agency for a written offer.</p>
HTML,
        ];
    }

    private static function pharmacyChecklist(): array
    {
        return [
            'key' => 'pharmacy-checklist',
            'slug' => 'pharmacy-pos-system-checklist-en',
            'days_ago' => 17,
            'title' => 'Choosing a Pharmacy POS and Management System: 8 Questions to Ask Before You Buy',
            'meta_title' => 'Pharmacy POS System: 8 Questions to Ask Before You Buy',
            'meta_description' => 'Eight questions for any company selling a pharmacy system: batches and expiry, shortages, prescriptions, returns, supplier balances and backups.',
            'excerpt' => 'A practical list of eight things to check in any pharmacy system, with a straight answer about what ours has and does not have.',
            'content' => <<<'HTML'
<p>A mistake in a pharmacy system costs money: an expired medicine sold, a shortage nobody was warned about, a supplier balance that is wrong. Before you pay for any system, ask these eight questions. We answer them for our own <a href="/services/pharmacy-system">pharmacy management system</a>, including what it does not have.</p>

<h2>1. Does it handle batches and expiry dates?</h2>
<p>One medicine arrives in several batches with different expiry dates. A system that keeps a single stock number for each medicine cannot tell you what expires first. Ask: does every purchase invoice create a batch, and which one is sold first?</p>
<p><strong>Ours:</strong> every purchase line creates a batch, and the cashier takes from the batch that expires first (FEFO). A line is split across two batches when the first one runs out. Expired stock is never sold.</p>

<h2>2. Expiry alerts and shortages</h2>
<p>An alert must come before the medicine expires, not after. Ask about the alert periods, and whether shortage counts ignore expired stock.</p>
<p><strong>Ours:</strong> an alerts page lists expired stock, expiry within 30 days, 31 to 60 days and 61 to 90 days, valued at cost. Shortage alerts use a reorder level per medicine and ignore expired stock.</p>

<h2>3. Selling by the box and by the strip</h2>
<p>A pharmacy sells by the box and by the strip. Ask how the system stores quantity and how it updates the balance after one strip is sold.</p>
<p><strong>Ours:</strong> stock is kept in strips, and you sell by box or by strip.</p>

<h2>4. A prescription flag</h2>
<p>Medicines that need a prescription should require an explicit step before the sale. Ask whether you can record the doctor and the patient.</p>
<p><strong>Ours:</strong> a medicine that needs a prescription asks for a "prescription seen" tick before the sale is completed, with optional doctor and patient names saved on the sale.</p>
<p><strong>Not included:</strong> a controlled-drug register. Only the prescription details on the sale are stored. If your pharmacy needs an official register for controlled medicines, this system alone is not enough.</p>

<h2>5. Returns</h2>
<p>Ask which batch a returned item goes back to, and how a return is handled when the original sale was on credit.</p>
<p><strong>Ours:</strong> a customer return goes back to the same batch unless it has expired. A credit sale lowers the customer debt, and a paid sale is refunded as a negative payment.</p>

<h2>6. Suppliers and balances</h2>
<p>Supplier balances and returns to suppliers are among the easiest places to get lost. Ask for a statement for each supplier.</p>
<p><strong>Ours:</strong> supplier balances and returns to supplier, stock adjustments, a write-off of all expired stock in one step, and branch transfers that take the earliest expiry first.</p>

<h2>7. Price labels and printing</h2>
<p>Ask about barcodes and the receipt sizes that fit your printer.</p>
<p><strong>Ours:</strong> shelf price labels on an A4 sheet or a 50×30 mm roll with a Code 39 barcode, and receipts in 80 mm, 58 mm and A4.</p>

<h2>8. Backups, and source code or hosted?</h2>
<p>Ask who makes a backup of your data and where it is kept. Also ask what happens if the company disappears.</p>
<p><strong>Ours:</strong> we sell the source code, and it is installed on your own hosting. Your data is with you, but backups come from your hosting or from a setup you make, not from us. Details are in our article on <a href="/blog/source-code-or-hosted-software-egypt">source code or hosted software</a>.</p>

<h2>What about the electronic receipt?</h2>
<p>The system includes a connector for the Egyptian Tax Authority e-receipt (ETA), built to the published specification. <strong>It has not been tested against the authority's real servers yet</strong>, it is not certified by us, and it needs your own registration and credentials. Ask your accountant whether your business must issue e-receipts.</p>

<h2>Price</h2>
<p>The source code as it is costs 5,000 EGP with no modifications, or 20,000 EGP with any programming change your business needs. Payment is once. The page has real screenshots of the system and a tour video, with demo data.</p>

<h2>Quick questions</h2>
<h3>Does it collect payments online?</h3>
<p>No. It records the payment method (cash, card, transfer, wallet) but does not collect the payment itself.</p>
<h3>Does it work with more than one branch?</h3>
<p>Yes. There are branches and transfers between them, and each branch has its own e-receipt settings.</p>
<h3>Can I try it before buying?</h3>
<p>The product page has real screenshots and a tour video. If you want to see something specific, ask us on WhatsApp before you pay.</p>
HTML,
        ];
    }

    private static function sourceOrHosted(): array
    {
        return [
            'key' => 'source-or-hosted',
            'slug' => 'source-code-or-hosted-software-egypt-en',
            'days_ago' => 9,
            'title' => 'Source Code or a Hosted Copy? What Fits Your Business',
            'meta_title' => 'Source Code or Hosted Software? What Fits Your Business',
            'meta_description' => 'An honest comparison of buying a system as source code on your own hosting versus a hosted subscription, and our 5,000 and 20,000 EGP plans.',
            'excerpt' => 'What you get and what you carry with each choice, how to decide, and what we sell today.',
            'content' => <<<'HTML'
<p>When you buy a system for your business (a pharmacy, a restaurant, a clinic, a shop) there are two main ways: you buy the <strong>source code</strong> and install it on your own hosting, or you take a <strong>hosted copy</strong> run by the company and pay a subscription. Neither is right for everyone. This article explains the difference plainly and says what we offer.</p>

<h2>Source code: what you get and what you carry</h2>
<h3>Advantages</h3>
<ul>
<li><strong>You own the code:</strong> you can change it, or hire another programmer to, without asking anyone.</li>
<li><strong>Your data is with you:</strong> the database is on your hosting, not the company's.</li>
<li><strong>No monthly fee for the system itself:</strong> you pay once, and afterwards only for hosting and a domain.</li>
<li><strong>Not tied to the company:</strong> if the company closes, the system keeps running.</li>
</ul>
<h3>Disadvantages</h3>
<ul>
<li><strong>Installation is on you:</strong> you need hosting that meets the requirements and written install steps. If you are not comfortable, you will need someone to help.</li>
<li><strong>Backups are on you:</strong> if the disk fails or the hosting is hit, your data is your responsibility.</li>
<li><strong>Updates are manual:</strong> nobody updates for you automatically, so you have to follow them.</li>
<li><strong>Security is on you:</strong> hosting updates, passwords and permissions.</li>
</ul>

<h2>A hosted copy: what you get and what you carry</h2>
<h3>Advantages</h3>
<ul>
<li>The company installs, runs and updates it, and you get a link and start.</li>
<li>Backups and maintenance are often part of the service, but ask.</li>
</ul>
<h3>Disadvantages</h3>
<ul>
<li><strong>A subscription that never ends:</strong> if you stop paying, the service may stop.</li>
<li><strong>Your data sits with someone else:</strong> ask how you can take a copy if you want to leave.</li>
<li><strong>Less flexibility:</strong> the changes you want compete with the company's priorities and are not always built.</li>
<li><strong>Dependence on the company:</strong> if it closes or changes its prices, your position is weaker.</li>
</ul>

<h2>How to decide</h2>
<ol>
<li><strong>Do you have a technical person, or can you learn a simple install?</strong> If yes, source code is cheaper over time.</li>
<li><strong>Is your data sensitive, and do you want control over it?</strong> Source code gives you more control.</li>
<li><strong>Do you want custom changes to your system?</strong> Source code gives you freedom, and we offer a plan with changes.</li>
<li><strong>Do you want to open it and work, with nothing technical at all?</strong> A hosted copy from a company that provides it may fit better, as long as you understand its long-term cost.</li>
</ol>

<h2>What we offer today</h2>
<p>We sell the <strong>source code</strong> of our systems (such as the <a href="/services/pharmacy-system">pharmacy</a>, <a href="/services/restaurant-pos-system">restaurant</a> and <a href="/services/dental-clinic-system">clinic</a> systems) in two plans, paid once, with hosting on you:</p>
<ul>
<li><strong>The code as it is, 5,000 EGP:</strong> the full Laravel source code, an install guide and demo data, with no modifications.</li>
<li><strong>The code with changes, 20,000 EGP:</strong> the same code with any programming change your business needs, and any change request is available. We agree the list with you on WhatsApp after payment.</li>
</ul>
<p>We do not currently sell a hosted copy on a subscription. The system installs on normal shared hosting (cPanel) that supports PHP 8.3 and an SQLite or MySQL database, with no Redis or extra services, and the install steps are in an included guide.</p>
<p>A plain warning: "any change" in the second plan means any programming change your system needs, but the amount of work is agreed in writing before we start. We keep the list explicit so nobody is surprised.</p>

<h2>Before you buy, ask any company</h2>
<ul>
<li>What exactly do I receive? The full code, or compiled files?</li>
<li>Does the license allow changing the code and running it on more than one branch?</li>
<li>If I buy a hosted copy: how do I export my data if I want to leave?</li>
<li>Who is responsible for backups and security in each case?</li>
</ul>

<h2>Quick questions</h2>
<h3>Can I move from source code to hosted later?</h3>
<p>Technically yes, because the code is yours. But that is not a service we offer today.</p>
<h3>How much will hosting cost?</h3>
<p>It depends on your hosting provider. It is a separate cost from the system price, and we do not set it.</p>
<h3>What if I cannot install it?</h3>
<p>Message us on WhatsApp with your order number. If you need help with the installation, we agree on that with you.</p>
HTML,
        ];
    }
}
