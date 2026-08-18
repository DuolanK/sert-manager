<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_on_create()
    {
        $task = Task::factory()->create(['title' => 'Test']);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'action' => 'created',
        ]);
    }

    public function test_audit_log_on_update()
    {
        $task = Task::factory()->create(['title' => 'Old']);
        $task->update(['title' => 'New']);

        $log = ActivityLog::where('subject_id', $task->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Old', $log->changes['before']['title']);
        $this->assertEquals('New', $log->changes['after']['title']);
    }

    public function test_audit_log_on_force_delete()
    {
        $task = Task::factory()->create(['title' => 'Test']);
        $task->delete();
        $task->forceDelete();

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'action' => 'deleted',
        ]);
    }
}
