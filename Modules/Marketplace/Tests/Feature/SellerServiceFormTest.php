<?php

namespace Modules\Marketplace\Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Tests\TestCase;

/** Posts exactly the fields the Blade seller forms send: no package description and no currency. */
class SellerServiceFormTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private ServiceCategory $category;

    private Currency $usd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['onboarding_completed' => true]);
        $this->category = ServiceCategory::create(['name' => 'Web', 'slug' => 'web-form-test']);
        $this->usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$', 'exchange_rate' => 1]);
    }

    public function test_the_create_form_saves_a_service_using_the_tagline_and_usd_for_the_package(): void
    {
        $this->actingAs($this->seller)
            ->post(route('marketplace.services.store'), $this->formFields(['packages' => [$this->formPackage()]]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $package = Service::where('title', 'My new service')->firstOrFail()->packages()->firstOrFail();

        $this->assertSame('A short line about the service', $package->description);
        $this->assertSame($this->usd->id, $package->currency_id);
        $this->assertSame(50.0, $package->price);
    }

    public function test_the_create_page_opens_the_full_form_for_a_seller(): void
    {
        $this->actingAs($this->seller)
            ->get(route('marketplace.services.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Marketplace/Services/Create')
                ->has('categories')
                ->has('currencies')
                ->where('needsReview', true)
                ->where('adminUrl', null));
    }

    public function test_the_full_create_form_saves_everything_as_a_draft_owned_by_the_seller(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->seller)->post(route('marketplace.services.store'), [
            'full_form' => '1',
            'seller_id' => $other->id,
            'status' => 'active',
            'auto_complete' => '1',
            'category_id' => $this->category->id,
            'title_translations' => ['en' => '', 'ar' => 'خدمة جديدة كاملة'],
            'tagline_translations' => ['en' => '', 'ar' => 'سطر قصير'],
            'description_translations' => ['en' => '', 'ar' => 'وصف طويل بما يكفي للخدمة الجديدة'],
            'auto_reply_translations' => ['en' => '', 'ar' => 'أهلا بيك'],
            'tags' => ['logo', 'design'],
            'video_url' => 'https://example.com/video',
            'requirements' => ['Your logo'],
            'faq' => [['question' => 'Question?', 'answer' => 'Answer.']],
            'extras' => [['title' => 'Rush', 'price' => 15, 'duration_days' => 1]],
            'packages' => [
                ['name' => 'Basic', 'description' => 'Basic text', 'price' => 10, 'currency_id' => $this->usd->id, 'delivery_days' => 2, 'revisions' => 1, 'features' => ['One']],
                ['name' => 'Gold', 'description' => 'Gold text', 'price' => 90, 'old_price' => 120, 'currency_id' => $this->usd->id, 'delivery_days' => 7, 'revisions' => 5],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $service = Service::where('title', 'خدمة جديدة كاملة')->firstOrFail();

        $this->assertSame($this->seller->id, $service->seller_id);
        $this->assertSame('draft', $service->status);
        $this->assertFalse((bool) $service->auto_complete);
        $this->assertSame('سطر قصير', $service->tagline);
        $this->assertSame('أهلا بيك', $service->auto_reply);
        $this->assertSame(['logo', 'design'], $service->tags);
        $this->assertSame('Rush', $service->extras()->firstOrFail()->title);
        $this->assertSame(2, $service->packages()->count());
        $this->assertSame(['One'], $service->packages()->where('name', 'Basic')->firstOrFail()->features);
        $this->assertSame(120.0, (float) $service->packages()->where('name', 'Gold')->firstOrFail()->old_price);
    }

    public function test_a_new_service_needs_a_title_and_a_description(): void
    {
        $this->actingAs($this->seller)->post(route('marketplace.services.store'), [
            'full_form' => '1',
            'category_id' => $this->category->id,
            'title_translations' => ['en' => '', 'ar' => ''],
            'description_translations' => ['en' => '', 'ar' => ''],
            'packages' => [[...$this->formPackage(), 'description' => 'Text', 'currency_id' => $this->usd->id]],
        ])->assertSessionHasErrors(['title', 'description']);
    }

    public function test_the_edit_form_keeps_the_saved_details_and_the_other_packages(): void
    {
        $service = Service::create(['seller_id' => $this->seller->id, 'category_id' => $this->category->id, 'title' => 'Old title', 'description' => 'A long enough description', 'status' => 'active']);
        $first = $this->savedPackage($service, 'Basic', 10);
        $second = $this->savedPackage($service, 'Premium', 90);

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), $this->formFields([
            'packages' => [
                [...$this->formPackage(), 'id' => $first->id, 'name' => 'Basic'],
                ['id' => $second->id],
            ],
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $first->refresh();
        $second->refresh();

        $this->assertSame(2, $service->packages()->count());
        $this->assertSame(50.0, $first->price);
        $this->assertSame('Saved text for Basic', $first->description);
        $this->assertSame(['Saved feature'], $first->features);
        $this->assertSame('Premium', $second->name);
        $this->assertSame(90.0, $second->price);
    }

    public function test_the_edit_form_changes_every_package_the_seller_edits(): void
    {
        $service = Service::create(['seller_id' => $this->seller->id, 'category_id' => $this->category->id, 'title' => 'Old title', 'description' => 'A long enough description', 'status' => 'active']);
        $first = $this->savedPackage($service, 'Basic', 10);
        $second = $this->savedPackage($service, 'Premium', 90);

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), $this->formFields([
            'packages' => [
                ['id' => $first->id, 'name' => 'Basic', 'description' => 'Basic text', 'price' => 10, 'delivery_days' => 4],
                ['id' => $second->id, 'name' => 'Gold', 'description' => 'Gold text', 'price' => 120, 'delivery_days' => 9, 'revisions' => 5],
            ],
        ]))->assertSessionHasNoErrors();

        $second->refresh();
        $this->assertSame('Gold', $second->name);
        $this->assertSame('Gold text', $second->description);
        $this->assertSame(120.0, $second->price);
        $this->assertSame(9, (int) $second->delivery_days);
        $this->assertSame(['Saved feature'], $second->features);
    }

    public function test_a_field_sent_empty_is_rejected_with_a_readable_name_and_keeps_the_saved_value(): void
    {
        $service = Service::create(['seller_id' => $this->seller->id, 'category_id' => $this->category->id, 'title' => 'Old title', 'description' => 'A long enough description', 'status' => 'active']);
        $package = $this->savedPackage($service, 'Basic', 10);

        $response = $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), $this->formFields([
            'packages' => [['id' => $package->id, 'name' => 'Basic', 'price' => '', 'delivery_days' => 4]],
        ]));

        $response->assertSessionHasErrors('packages.0.price');
        $message = session('errors')->first('packages.0.price');
        $this->assertStringNotContainsString('packages.0', $message);
        $this->assertStringContainsString('package 1 price', $message);
        $this->assertSame('سعر الباقة :position', trans('validation.attributes', [], 'ar')['packages.*.price']);
        $this->assertSame(10.0, $package->fresh()->price);
    }

    public function test_saving_the_edit_form_keeps_every_field_it_does_not_show(): void
    {
        $service = $this->fullService();
        $extra = $service->extras()->create(['title' => 'Fast delivery', 'price' => 5, 'duration_days' => 1]);
        $package = $this->savedPackage($service, 'Basic', 10);

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), [
            ...$this->sameTextFields($service),
            'packages' => [[...$this->formPackage(), 'id' => $package->id, 'name' => 'Basic']],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $service->refresh();
        $this->assertSame(['services/1/a.jpg', 'services/1/b.jpg'], $service->gallery);
        $this->assertSame('services/1/thumb.jpg', $service->getRawOriginal('thumbnail'));
        $this->assertSame(['logo'], $service->tags);
        $this->assertSame([['question' => 'Q', 'answer' => 'A']], $service->faq);
        $this->assertSame('https://example.com/video', $service->video_url);
        $this->assertSame('https://example.com/file', $service->service_link);
        $this->assertTrue($service->is_free);
        $this->assertTrue($service->generate_serials);
        $this->assertSame(30, (int) $service->validity_days);
        $this->assertSame('Keep me', $service->title_translations['en']);
        $this->assertSame('original-slug', $service->slug);
        $this->assertNotNull($extra->fresh());
    }

    public function test_changing_the_title_replaces_its_translations_and_slug(): void
    {
        $service = $this->fullService();
        $package = $this->savedPackage($service, 'Basic', 10);

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), [
            ...$this->sameTextFields($service),
            'title' => 'A brand new title',
            'packages' => [[...$this->formPackage(), 'id' => $package->id, 'name' => 'Basic']],
        ])->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame('A brand new title', $service->title);
        $this->assertNull($service->title_translations);
        $this->assertSame('a-brand-new-title', $service->slug);
    }

    public function test_a_seller_can_add_and_remove_packages_from_the_edit_form(): void
    {
        $service = $this->fullService();
        $kept = $this->savedPackage($service, 'Basic', 10);
        $removed = $this->savedPackage($service, 'Old', 30);

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), [
            ...$this->sameTextFields($service),
            'packages' => [
                ['id' => $kept->id],
                ['id' => '', 'name' => 'Added', 'description' => 'Added text', 'price' => 70, 'delivery_days' => 8],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSoftDeleted('marketplace_packages', ['id' => $removed->id]);
        $this->assertSame(['Basic', 'Added'], $service->packages()->orderBy('id')->pluck('name')->all());
        $this->assertSame($this->usd->id, $service->packages()->where('name', 'Added')->value('currency_id'));
    }

    public function test_the_edit_page_opens_the_full_form_with_everything_saved(): void
    {
        $service = $this->fullService();
        $this->savedPackage($service, 'Basic', 10);
        $service->extras()->create(['title' => 'Fast delivery', 'price' => 5, 'duration_days' => 1]);

        $this->actingAs($this->seller)->get(route('marketplace.services.edit', $service))->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Marketplace/Services/Edit')
                ->where('service.tags', ['logo'])
                ->where('service.video_url', 'https://example.com/video')
                ->where('service.packages.0.features', ['Saved feature'])
                ->where('service.extras.0.title', 'Fast delivery')
                ->where('needsReview', true)
                ->where('adminUrl', null)
                ->has('currencies')
        );
    }

    public function test_the_full_form_can_empty_every_list_even_when_a_file_upload_drops_them(): void
    {
        $service = $this->fullService();
        $package = $this->savedPackage($service, 'Basic', 10);
        $extra = $service->extras()->create(['title' => 'Fast delivery', 'price' => 5, 'duration_days' => 1]);

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), [
            ...$this->sameTextFields($service),
            'full_form' => '1',
            'packages' => [['id' => $package->id, 'name' => 'Basic', 'description' => 'Text', 'price' => 10, 'delivery_days' => 4]],
        ])->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame([], $service->gallery);
        $this->assertSame([], $service->tags);
        $this->assertSame([], $service->faq);
        $this->assertSame([], $package->fresh()->features);
        $this->assertSoftDeleted('marketplace_service_extras', ['id' => $extra->id]);
    }

    public function test_translated_text_fills_the_plain_columns_and_a_taken_title_gets_its_own_address(): void
    {
        Service::create(['seller_id' => $this->seller->id, 'category_id' => $this->category->id, 'title' => 'Taken', 'slug' => 'new-name', 'description' => 'A long enough description', 'status' => 'active']);
        $service = $this->fullService();
        $package = $this->savedPackage($service, 'Basic', 10);

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), [
            'category_id' => $this->category->id,
            'full_form' => '1',
            'title_translations' => ['en' => 'New name', 'ar' => 'اسم جديد'],
            'description_translations' => ['en' => null, 'ar' => 'وصف عربي طويل بما يكفي للخدمة'],
            'packages' => [['id' => $package->id]],
        ])->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame('New name', $service->title);
        $this->assertSame('اسم جديد', $service->localizedTitle('ar'));
        $this->assertSame('وصف عربي طويل بما يكفي للخدمة', $service->description);
        $this->assertSame('new-name-'.$service->id, $service->slug);
    }

    public function test_a_seller_cannot_read_another_service_package_through_its_id(): void
    {
        $service = Service::create(['seller_id' => $this->seller->id, 'category_id' => $this->category->id, 'title' => 'Mine', 'description' => 'A long enough description', 'status' => 'active']);
        $own = $this->savedPackage($service, 'Own', 10);
        $other = Service::create(['seller_id' => User::factory()->create()->id, 'category_id' => $this->category->id, 'title' => 'Theirs', 'description' => 'A long enough description', 'status' => 'active']);
        $foreign = $this->savedPackage($other, 'Foreign', 70);

        $this->actingAs($this->seller)
            ->put(route('marketplace.services.update', $service), $this->formFields(['packages' => [['id' => $own->id], ['id' => $foreign->id]]]))
            ->assertSessionHasErrors('packages.1.price');

        $this->assertSame(70.0, $foreign->fresh()->price);
    }

    private function fullService(): Service
    {
        $service = Service::create(['seller_id' => $this->seller->id, 'category_id' => $this->category->id, 'title' => 'Keep me', 'description' => 'A long enough description', 'status' => 'active']);
        $service->forceFill([
            'slug' => 'original-slug',
            'thumbnail' => 'services/1/thumb.jpg',
            'gallery' => ['services/1/a.jpg', 'services/1/b.jpg'],
            'tags' => ['logo'],
            'faq' => [['question' => 'Q', 'answer' => 'A']],
            'video_url' => 'https://example.com/video',
            'service_link' => 'https://example.com/file',
            'is_free' => true,
            'generate_serials' => true,
            'validity_days' => 30,
            'title_translations' => ['en' => 'Keep me', 'ar' => 'احتفظ بي'],
        ])->save();

        return $service->fresh();
    }

    /** @return array<string, mixed> */
    private function sameTextFields(Service $service): array
    {
        return ['title' => $service->title, 'category_id' => $this->category->id, 'description' => $service->description, 'requirements' => '', 'auto_reply' => ''];
    }

    private function savedPackage(Service $service, string $name, float $price): ServicePackage
    {
        return ServicePackage::create([
            'service_id' => $service->id,
            'name' => $name,
            'description' => "Saved text for {$name}",
            'price' => $price,
            'currency_id' => $this->usd->id,
            'delivery_days' => 4,
            'revisions' => 1,
            'features' => ['Saved feature'],
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function formFields(array $extra): array
    {
        return [
            'title' => 'My new service',
            'category_id' => $this->category->id,
            'tagline' => 'A short line about the service',
            'description' => 'A long enough description of the service.',
            'requirements' => "First need\nSecond need",
            'auto_reply' => '',
            ...$extra,
        ];
    }

    /** @return array<string, mixed> */
    private function formPackage(): array
    {
        return ['name' => 'Standard Package', 'tier' => 'standard', 'price' => 50, 'delivery_days' => 3, 'revisions' => 2];
    }
}
