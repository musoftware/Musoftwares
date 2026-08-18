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
        $response->assertSee('معرض الكتب والمطبوعات الرقمية');
    }

    public function test_admin_can_upload_new_book(): void
    {
        Storage::fake('local');

        $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Count 42 /Type /Pages >>\nendobj\n";
        $pdfFile = UploadedFile::fake()->createWithContent('ai-handbook.pdf', $pdfContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.digitalproducts.store'), [
                'title' => 'كتيب الذكاء الاصطناعي الشامل',
                'price' => 0.00,
                'is_free' => 1,
                'page_count' => 42,
                'pdf_file' => $pdfFile,
                'is_published' => 1,
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.digitalproducts.index'));

        $this->assertDatabaseHas('digital_products', [
            'title' => 'كتيب الذكاء الاصطناعي الشامل',
            'page_count' => 42,
            'is_free' => 1,
        ]);
    }
}
