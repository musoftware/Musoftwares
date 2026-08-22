<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\DigitalProducts\Models\DigitalCategory;
use Modules\DigitalProducts\Models\DigitalProduct;
use Tests\TestCase;

class DigitalProductsLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_library_index_page_loads_successfully(): void
    {
        $response = $this->get(route('library.index'));
        $response->assertStatus(200);
        $response->assertSee('المكتبة الرقمية');
    }

    public function test_library_book_details_page_loads_with_seo_and_schema(): void
    {
        $category = DigitalCategory::create([
            'name' => 'اختبار الذكاء الاصطناعي',
            'slug' => 'test-ai-category',
            'is_active' => true,
        ]);

        $book = DigitalProduct::create([
            'title' => 'كتاب الذكاء الاصطناعي التجريبي',
            'slug' => 'test-ai-book-guide',
            'category_id' => $category->id,
            'price' => 0.00,
            'is_free' => true,
            'file_path' => 'books/files/sample.pdf',
            'page_count' => 45,
            'author_name' => 'Musoftware AI Lab',
            'is_published' => true,
        ]);

        $response = $this->get(route('library.show', $book->slug));
        $response->assertStatus(200);
        $response->assertSee($book->title);
        $response->assertSee('https://schema.org');
        $response->assertSee('"@type": "Book"', false);
    }

    public function test_free_book_email_download_generates_token_and_serves_download(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('books/files/free-book.pdf', '%PDF-1.4 Fake PDF Content');

        $book = DigitalProduct::create([
            'title' => 'Free Download Book',
            'slug' => 'free-download-book',
            'price' => 0.00,
            'is_free' => true,
            'file_path' => 'books/files/free-book.pdf',
            'page_count' => 20,
            'is_published' => true,
        ]);

        $response = $this->post(route('library.free_download', $book->slug), [
            'email' => 'reader@example.com',
        ]);

        $response->assertStatus(302);

        $downloadRecord = $book->downloads()->where('email', 'reader@example.com')->first();
        $this->assertNotNull($downloadRecord);

        // Download via token
        $downloadRes = $this->get(route('library.download.token', $downloadRecord->download_token));
        $downloadRes->assertStatus(200);
        $this->assertEquals('attachment; filename=free-download-book.pdf', $downloadRes->headers->get('content-disposition'));
    }

    public function test_dual_edition_book_allows_free_playbook_download_and_paid_full_book(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('books/files/full-ai-book.pdf', '%PDF-1.4 Full 120 Pages Master Book');
        Storage::disk('local')->put('books/files/playbook-ai.pdf', '%PDF-1.4 Free 20 Pages Playbook');

        $book = DigitalProduct::create([
            'title' => 'AI Business Master Book',
            'slug' => 'ai-business-master-book',
            'price' => 49.00,
            'is_free' => false,
            'file_path' => 'books/files/full-ai-book.pdf',
            'page_count' => 120,
            'has_free_edition' => true,
            'free_edition_title' => 'AI Business Fast Playbook',
            'free_edition_file_path' => 'books/files/playbook-ai.pdf',
            'free_edition_page_count' => 20,
            'is_published' => true,
        ]);

        // 1. Check show page displays both options
        $showRes = $this->get(route('library.show', $book->slug));
        $showRes->assertStatus(200);
        $showRes->assertSee('نسخة مجانية (Playbook)');
        $showRes->assertSee('AI Business Fast Playbook');
        $showRes->assertSee('الإصدار الكامل المعتمد');

        // 2. Request Free Playbook Download
        $freePlaybookRes = $this->post(route('library.free_download', $book->slug), [
            'email' => 'playbook.reader@example.com',
            'edition_type' => 'playbook',
        ]);
        $freePlaybookRes->assertStatus(302);

        $downloadRecord = $book->downloads()->where('email', 'playbook.reader@example.com')->first();
        $this->assertNotNull($downloadRecord);
        $this->assertEquals('playbook', $downloadRecord->edition_type);

        // 3. Download Playbook by token
        $playbookFileRes = $this->get(route('library.download.token', $downloadRecord->download_token));
        $playbookFileRes->assertStatus(200);
        $this->assertEquals('attachment; filename=playbook-ai-business-master-book.pdf', $playbookFileRes->headers->get('content-disposition'));

        $this->assertEquals(1, $book->fresh()->free_edition_download_count);

        // 4. Buy Full Book with Wallet
        $this->clientUser->add_balance(100.00, 'Test Deposit', 'deposit', 1);
        $buyResponse = $this->actingAs($this->clientUser)->post(route('library.buy.wallet', $book->slug));
        $buyResponse->assertStatus(302);
        $buyResponse->assertRedirect(route('library.my_library'));

        // 5. Permanent Full Download
        $fullDownloadRes = $this->actingAs($this->clientUser)->get(route('library.my_library.download', $book->slug));
        $fullDownloadRes->assertStatus(200);
        $this->assertEquals('attachment; filename=ai-business-master-book.pdf', $fullDownloadRes->headers->get('content-disposition'));
    }

    public function test_paid_book_wallet_purchase_and_permanent_download(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('books/files/paid-playbook.pdf', '%PDF-1.4 Paid Book Content');

        $book = DigitalProduct::create([
            'title' => 'Paid AI Playbook',
            'slug' => 'paid-ai-playbook',
            'price' => 25.00,
            'is_free' => false,
            'file_path' => 'books/files/paid-playbook.pdf',
            'page_count' => 80,
            'is_published' => true,
        ]);

        // Add wallet balance to client
        $this->clientUser->add_balance(100.00, 'Test Deposit', 'deposit', 1);

        // Buy book with wallet
        $buyResponse = $this->actingAs($this->clientUser)
            ->post(route('library.buy.wallet', $book->slug));

        $buyResponse->assertStatus(302);
        $buyResponse->assertRedirect(route('library.my_library'));

        $this->assertTrue($book->isPurchasedBy($this->clientUser));

        // Test permanent download from My Library
        $downloadRes = $this->actingAs($this->clientUser)
            ->get(route('library.my_library.download', $book->slug));

        $downloadRes->assertStatus(200);
        $this->assertEquals('attachment; filename=paid-ai-playbook.pdf', $downloadRes->headers->get('content-disposition'));
    }

    public function test_admin_can_access_books_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.digitalproducts.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/DigitalProducts/Index'));
    }

    public function test_admin_can_upload_new_book_with_dual_editions(): void
    {
        Storage::fake('local');

        $fullPdfContent = "%PDF-1.4\n1 0 obj\n<< /Count 120 /Type /Pages >>\nendobj\n";
        $fullPdfFile = UploadedFile::fake()->createWithContent('full-book.pdf', $fullPdfContent);

        $playbookPdfContent = "%PDF-1.4\n1 0 obj\n<< /Count 20 /Type /Pages >>\nendobj\n";
        $playbookPdfFile = UploadedFile::fake()->createWithContent('playbook-summary.pdf', $playbookPdfContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.digitalproducts.store'), [
                'title' => 'كتاب نماذج الأعمال والذكاء الاصطناعي',
                'price' => 35.00,
                'currency_id' => 1,
                'is_free' => 0,
                'page_count' => 120,
                'pdf_file' => $fullPdfFile,
                'has_free_edition' => 1,
                'free_edition_title' => 'ملخص الـ Playbook المجاني',
                'free_edition_pdf_file' => $playbookPdfFile,
                'free_edition_page_count' => 20,
                'is_published' => 1,
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.digitalproducts.index'));

        $this->assertDatabaseHas('digital_products', [
            'title' => 'كتاب نماذج الأعمال والذكاء الاصطناعي',
            'price' => 35.00,
            'currency_id' => 1,
            'page_count' => 120,
            'has_free_edition' => 1,
            'free_edition_page_count' => 20,
        ]);
    }

    public function test_multi_currency_exchange_and_wallet_deduction(): void
    {
        \App\Models\CurrenciesExchange::flushCache();

        $usd = Currency::firstOrCreate(['id' => 1], [
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '$%01.2f',
            'is_default' => true,
        ]);

        $egp = Currency::firstOrCreate(['id' => 2], [
            'currency' => 'EGP',
            'symbol' => 'e£',
            'string_format' => 'e£%01.2f',
            'is_default' => false,
        ]);

        // Exchange rates: 1 USD = 50 EGP, 1 EGP = 0.02 USD
        \App\Models\CurrenciesExchange::updateOrCreate(
            ['currency1' => $usd->id, 'currency2' => $egp->id, 'date_string' => now('Africa/Cairo')->toDateString()],
            ['rate' => 50.0]
        );
        \App\Models\CurrenciesExchange::updateOrCreate(
            ['currency1' => $egp->id, 'currency2' => $usd->id, 'date_string' => now('Africa/Cairo')->toDateString()],
            ['rate' => 0.02]
        );
        \App\Models\CurrenciesExchange::flushCache();

        Storage::fake('local');
        Storage::disk('local')->put('books/files/cloud-mastery.pdf', '%PDF-1.4 Cloud Mastery Guide');

        // Book priced in USD ($10.00)
        $book = DigitalProduct::create([
            'title' => 'Cloud Architecture Mastery',
            'slug' => 'cloud-architecture-mastery',
            'price' => 10.00,
            'currency_id' => $usd->id,
            'is_free' => false,
            'file_path' => 'books/files/cloud-mastery.pdf',
            'page_count' => 95,
            'is_published' => true,
        ]);

        // Client whose wallet currency is EGP (currency_id = 2)
        $egpUser = User::factory()->create([
            'currency_id' => $egp->id,
            'onboarding_completed' => true,
        ]);
        $egpUser->assignRole('client');

        // Deposit 1,000 EGP into client wallet
        $egpUser->add_balance(1000.00, 'EGP Deposit', 'deposit', $egp->id);

        // Check show page when logged in as EGP user
        $showRes = $this->actingAs($egpUser)->get(route('library.show', $book->slug));
        $showRes->assertStatus(200);
        // Required amount should be 500 EGP (10 USD * 50)
        $showRes->assertSee('500.00');

        // Execute Purchase
        $buyResponse = $this->actingAs($egpUser)->post(route('library.buy.wallet', $book->slug));
        $buyResponse->assertStatus(302);
        $buyResponse->assertRedirect(route('library.my_library'));

        // Assert book is purchased
        $this->assertTrue($book->isPurchasedBy($egpUser));

        // Assert purchase record has amount_paid = 500 in EGP
        $purchase = $book->purchases()->where('user_id', $egpUser->id)->first();
        $this->assertNotNull($purchase);
        $this->assertEquals(500.00, (float) $purchase->amount_paid);
        $this->assertEquals($egp->id, $purchase->currency_id);

        // Assert remaining available balance is 500 EGP
        $this->assertEquals(500.00, (float) $egpUser->fresh()->available_balance());
    }
}
