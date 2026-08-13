<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $this->token = $response->json('token');
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_list_certificates()
    {
        Certificate::factory()->count(3)->create();

        $response = $this->getJson('/api/certificates', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_search_certificates()
    {
        Certificate::factory()->create(['name' => 'Birthday Gift']);
        Certificate::factory()->create(['name' => 'New Year Bonus']);

        $response = $this->getJson('/api/certificates?search=Birthday', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Birthday Gift');
    }

    public function test_filter_by_status()
    {
        Certificate::factory()->create(['status' => 'active']);
        Certificate::factory()->create(['status' => 'expired']);
        Certificate::factory()->create(['status' => 'active']);

        $response = $this->getJson('/api/certificates?status=active', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_create_certificate()
    {
        $response = $this->postJson('/api/certificates', [
            'name' => 'Test Certificate',
            'price' => 1000,
            'expires_at' => now()->addYear()->toDateString(),
            'status' => 'active',
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Test Certificate')
            ->assertJsonPath('price', '1000.00');

        $this->assertDatabaseHas('certificates', ['name' => 'Test Certificate']);
    }

    public function test_create_certificate_validation()
    {
        $response = $this->postJson('/api/certificates', [
            'name' => '',
            'price' => -5,
            'expires_at' => '2020-01-01',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'price', 'expires_at']]);
    }

    public function test_show_certificate()
    {
        $cert = Certificate::factory()->create();

        $response = $this->getJson("/api/certificates/{$cert->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('id', $cert->id);
    }

    public function test_update_certificate()
    {
        $cert = Certificate::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/certificates/{$cert->id}", [
            'name' => 'New Name',
        ], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('name', 'New Name');
    }

    public function test_soft_delete_certificate()
    {
        $cert = Certificate::factory()->create();

        $response = $this->deleteJson("/api/certificates/{$cert->id}", [], $this->authHeaders());

        $response->assertStatus(204);
        $this->assertSoftDeleted($cert);
    }

    public function test_restore_certificate()
    {
        $cert = Certificate::factory()->create();
        $cert->delete();

        $response = $this->postJson("/api/certificates/{$cert->id}/restore", [], $this->authHeaders());

        $response->assertStatus(200);
        $this->assertDatabaseHas('certificates', ['id' => $cert->id, 'deleted_at' => null]);
    }

    public function test_force_delete_certificate()
    {
        $cert = Certificate::factory()->create();
        $cert->delete();

        $response = $this->deleteJson("/api/certificates/{$cert->id}/force", [], $this->authHeaders());

        $response->assertStatus(204);
        $this->assertDatabaseMissing('certificates', ['id' => $cert->id]);
    }

    public function test_unauthorized_access()
    {
        $response = $this->getJson('/api/certificates');

        $response->assertStatus(401);
    }
}
