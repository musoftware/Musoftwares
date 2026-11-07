<?php

namespace Modules\Marketplace\Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_the_create_form_shows_the_form_with_no_package_description_field(): void
    {
        $this->actingAs($this->seller)
            ->get(route('marketplace.services.create'))
            ->assertOk()
            ->assertDontSee('packages[0][description]', false);
    }

    public function test_the_edit_form_keeps_the_saved_details_and_the_other_packages(): void
    {
        $service = Service::create(['seller_id' => $this->seller->id, 'category_id' => $this->category->id, 'title' => 'Old title', 'description' => 'A long enough description', 'status' => 'active']);
        $first = $this->savedPackage($service, 'Basic', 10);
        $second = $this->savedPackage($service, 'Premium', 90);

        $html = $this->actingAs($this->seller)->get(route('marketplace.services.edit', $service))->assertOk()->getContent();
        $this->assertStringContainsString('name="packages[1][id]" value="'.$second->id.'"', $html);
        $this->assertStringContainsString('name="packages[1][price]"', $html);
        $this->assertStringContainsString('Saved text for Premium', $html);

        $this->put(route('marketplace.services.update', $service), $this->formFields([
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
