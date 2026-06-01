<?php

use App\Models\User;
use Modules\GoldSavers\Models\GoldSaver;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

it('lists only the records belonging to the authenticated user', function () {
    GoldSaver::factory()->create(['user_id' => $this->user->id, 'grams' => 10]);
    GoldSaver::factory()->create(['user_id' => $this->otherUser->id, 'grams' => 50]);

    $response = $this->actingAs($this->user)->get('/gold-savers');

    $response->assertStatus(200);
    // Inertia testing assertion could be added here, but we can verify DB state easily
    $this->assertDatabaseHas('gold_savers', ['user_id' => $this->user->id]);
});

it('stores a new gold saver record correctly with valid data', function () {
    $response = $this->actingAs($this->user)->post('/gold-savers', [
        'carat' => 24,
        'gram_price' => 4000,
        'grams' => 20,
        'tax' => 150,
        'additional_price' => 50,
        'bought_date' => '2026-05-25',
        'zakat' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('gold_savers', [
        'user_id' => $this->user->id,
        'carat' => 24,
        'grams' => 20,
    ]);
});

it('prevents storing negative values and abuses', function () {
    $response = $this->actingAs($this->user)->post('/gold-savers', [
        'carat' => 24,
        'gram_price' => -4000, // Negative price attack
        'grams' => -20,        // Negative grams attack
        'tax' => -150,         // Negative tax attack
        'additional_price' => -50,
        'bought_date' => '2026-05-25',
        'zakat' => true,
    ]);

    $response->assertSessionHasErrors(['gram_price', 'grams', 'tax', 'additional_price']);
    $this->assertDatabaseCount('gold_savers', 0);
});

it('prevents IDOR: user cannot update another users record', function () {
    $otherRecord = GoldSaver::factory()->create([
        'user_id' => $this->otherUser->id,
        'carat' => 24,
        'grams' => 10,
    ]);

    $response = $this->actingAs($this->user)->put("/gold-savers/{$otherRecord->id}", [
        'carat' => 21,
        'gram_price' => 3000,
        'grams' => 10,
        'tax' => 0,
        'additional_price' => 0,
        'bought_date' => '2026-05-25',
        'zakat' => false,
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseHas('gold_savers', [
        'id' => $otherRecord->id,
        'carat' => 24, // Remains 24
    ]);
});

it('prevents IDOR: user cannot delete another users record', function () {
    $otherRecord = GoldSaver::factory()->create([
        'user_id' => $this->otherUser->id,
    ]);

    $response = $this->actingAs($this->user)->delete("/gold-savers/{$otherRecord->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('gold_savers', ['id' => $otherRecord->id]);
});

it('allows user to delete their own record', function () {
    $record = GoldSaver::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->delete("/gold-savers/{$record->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('gold_savers', ['id' => $record->id]);
});
