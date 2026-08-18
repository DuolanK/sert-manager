<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
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

    public function test_list_tasks()
    {
        Task::factory()->count(3)->create();

        $response = $this->getJson('/api/tasks', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_search_tasks()
    {
        Task::factory()->create(['title' => 'Write report']);
        Task::factory()->create(['title' => 'Fix bug']);

        $response = $this->getJson('/api/tasks?search=report', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Write report');
    }

    public function test_filter_by_completed()
    {
        Task::factory()->create(['completed' => true]);
        Task::factory()->create(['completed' => false]);
        Task::factory()->create(['completed' => false]);

        $response = $this->getJson('/api/tasks?completed=true', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_create_task()
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'New task',
            'executor' => 'Alice',
            'due_date' => now()->addDay()->toDateString(),
            'completed' => false,
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('title', 'New task')
            ->assertJsonPath('executor', 'Alice')
            ->assertJsonPath('completed', false);

        $this->assertDatabaseHas('tasks', ['title' => 'New task']);
    }

    public function test_create_task_validation()
    {
        $response = $this->postJson('/api/tasks', [
            'title' => '',
            'due_date' => 'not-a-date',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['title', 'due_date']]);
    }

    public function test_show_task()
    {
        $task = Task::factory()->create();

        $response = $this->getJson("/api/tasks/{$task->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('id', $task->id);
    }

    public function test_update_task()
    {
        $task = Task::factory()->create(['title' => 'Old title']);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'New title',
        ], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('title', 'New title');
    }

    public function test_toggle_completed()
    {
        $task = Task::factory()->create(['completed' => false]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'completed' => true,
        ], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('completed', true);
    }

    public function test_soft_delete_task()
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}", [], $this->authHeaders());

        $response->assertStatus(204);
        $this->assertSoftDeleted($task);
    }

    public function test_restore_task()
    {
        $task = Task::factory()->create();
        $task->delete();

        $response = $this->postJson("/api/tasks/{$task->id}/restore", [], $this->authHeaders());

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }

    public function test_force_delete_task()
    {
        $task = Task::factory()->create();
        $task->delete();

        $response = $this->deleteJson("/api/tasks/{$task->id}/force", [], $this->authHeaders());

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_unauthorized_access()
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }
}
