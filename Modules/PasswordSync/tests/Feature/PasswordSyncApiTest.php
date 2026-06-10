<?php

namespace Modules\PasswordSync\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PasswordSync\Models\PasswordVault;
use Laravel\Sanctum\Sanctum;

class PasswordSyncApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_empty_vault()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/vault/sync');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'vault' => null
                 ]);
        
        $this->assertDatabaseHas('password_vaults', [
            'user_id' => $user->id,
            'encrypted_data' => null
        ]);
    }

    public function test_authenticated_user_can_update_vault()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $encryptedPayload = 'eyJpdiI6IkFBQUFBQSIsImRhdGEiOiJCQkJCQkIifQ==';

        $response = $this->postJson('/api/vault/sync', [
            'vault' => $encryptedPayload
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Vault updated successfully'
                 ]);

        $this->assertDatabaseHas('password_vaults', [
            'user_id' => $user->id,
            'encrypted_data' => $encryptedPayload
        ]);
    }

    public function test_unauthenticated_user_cannot_access_vault()
    {
        $response = $this->getJson('/api/vault/sync');
        $response->assertStatus(401);

        $response = $this->postJson('/api/vault/sync', ['vault' => 'test']);
        $response->assertStatus(401);
    }
}
