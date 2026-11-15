<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Services\DriveBook;
use Tests\TestCase;

class DigitalProductsDriveAndSeoTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Feature\Concerns\SeedsUsdEgpRates;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
        $this->seed(RolesAndPermissionsSeeder::class);
        app()->setLocale('ar');
        Storage::fake('local');

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_only_drive_file_links_give_a_file_id(): void
    {
        $this->assertSame('1AbCdEfGhIjKlMn', DriveBook::fileId('https://drive.google.com/file/d/1AbCdEfGhIjKlMn/view?usp=sharing'));
        $this->assertSame('1AbCdEfGhIjKlMn', DriveBook::fileId('https://drive.google.com/open?id=1AbCdEfGhIjKlMn'));
        $this->assertSame('1AbCdEfGhIjKlMn', DriveBook::fileId('https://drive.usercontent.google.com/download?id=1AbCdEfGhIjKlMn&export=download'));
        $this->assertNull(DriveBook::fileId('https://evil.example.com/file/d/1AbCdEfGhIjKlMn/view'));
        $this->assertNull(DriveBook::fileId('http://127.0.0.1/?id=1AbCdEfGhIjKlMn'));
        $this->assertNull(DriveBook::fileId('not a link'));
    }

    public function test_the_admin_adds_a_book_from_a_drive_link(): void
    {
        Http::fake(['drive.usercontent.google.com/*' => Http::response("%PDF-1.4\n<< /Count 42 /Type /Pages >>\n", 200, [
            'Content-Disposition' => "attachment; filename*=UTF-8''%D9%83%D8%AA%D8%A7%D8%A8.pdf",
        ])]);

        $fetched = $this->actingAs($this->admin)
            ->postJson(route('admin.digitalproducts.drive.fetch'), ['link' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMn/view'])
            ->assertOk()
            ->assertJsonPath('name', 'كتاب.pdf')
            ->json();

        $this->get($fetched['preview_url'])->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->post(route('admin.digitalproducts.store'), [
            'title' => 'كتاب من درايف',
            'pdf_token' => $fetched['token'],
            'is_free' => 1,
            'is_published' => 1,
        ])->assertRedirect(route('admin.digitalproducts.index'));

        $book = DigitalProduct::where('title', 'كتاب من درايف')->firstOrFail();
        $this->assertSame(42, (int) $book->page_count);
        Storage::disk('local')->assertExists($book->file_path);
    }

    public function test_a_drive_link_that_is_not_shared_is_explained(): void
    {
        Http::fake(['drive.usercontent.google.com/*' => Http::response('<html>Sign in</html>', 200)]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.digitalproducts.drive.fetch'), ['link' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMn/view'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('admin.digital_products_drive_not_shared'));
    }

    public function test_a_link_that_is_not_drive_is_never_fetched(): void
    {
        Http::fake();

        $this->actingAs($this->admin)
            ->postJson(route('admin.digitalproducts.drive.fetch'), ['link' => 'http://169.254.169.254/latest/meta-data'])
            ->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_a_book_cannot_be_saved_with_a_missing_drive_file(): void
    {
        $this->actingAs($this->admin)->post(route('admin.digitalproducts.store'), [
            'title' => 'بدون ملف',
            'pdf_token' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        ])->assertSessionHasErrors('pdf_file');
    }

    public function test_customers_cannot_open_the_book_admin(): void
    {
        $customer = User::factory()->create(['onboarding_completed' => true]);
        $customer->assignRole('client');

        $this->actingAs($customer)->get(route('admin.digitalproducts.index'))->assertForbidden();
        $this->actingAs($customer)->postJson(route('admin.digitalproducts.drive.fetch'), ['link' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMn/view'])->assertForbidden();
    }

    public function test_a_shared_book_link_shows_the_book_title_and_cover(): void
    {
        $book = $this->book();

        $this->get(route('library.show', $book->slug))->assertOk()
            ->assertSee('<meta property="og:title" content="دليل التسويق | مكتبة Musoftware">', false)
            ->assertSee('<meta property="og:image" content="'.asset('storage/books/covers/guide.webp').'">', false)
            ->assertSee('<meta property="og:type" content="book">', false)
            ->assertSee('<link rel="canonical" href="'.route('library.show', $book->slug).'">', false)
            ->assertSee('<meta name="description" content="دليل عملي قصير">', false);
    }

    public function test_the_old_admin_book_link_opens_the_book(): void
    {
        $book = $this->book();

        $this->get('/library/'.$book->slug)->assertRedirect(route('library.show', $book->slug))->assertStatus(301);
        $this->get(route('library.my_library'))->assertRedirect();
    }

    public function test_a_book_whose_slug_changed_keeps_its_first_link(): void
    {
        $book = $this->book();
        $book->update(['title' => 'تعلم C++', 'slug' => 'learn-CPP']);

        $this->get('/library/book/taalm-c')->assertStatus(301)->assertRedirect(route('library.show', 'learn-CPP'));
        $this->get('/library/book/no-such-book')->assertNotFound();
        $this->get('/library/book/learn-CPP/free-download')->assertRedirect(route('library.show', 'learn-CPP'));
    }

    public function test_the_sitemap_lists_the_library_and_its_books(): void
    {
        $book = $this->book();

        $this->get(route('sitemap.part', 'pages'))->assertOk()
            ->assertSee(route('library.index'), false)
            ->assertSee(route('library.show', $book->slug), false);
    }

    private function book(): DigitalProduct
    {
        return DigitalProduct::create([
            'title' => 'دليل التسويق',
            'slug' => 'marketing-guide',
            'price' => 0,
            'is_free' => true,
            'file_path' => 'books/files/guide.pdf',
            'cover_image_path' => 'storage/books/covers/guide.webp',
            'short_description' => 'دليل عملي قصير',
            'page_count' => 30,
            'is_published' => true,
        ]);
    }
}
