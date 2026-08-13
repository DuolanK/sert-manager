<?php

namespace Tests\Unit;

use App\Models\Certificate;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_on_create()
    {
        $cert = Certificate::factory()->create(['name' => 'Test']);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Certificate::class,
            'subject_id' => $cert->id,
            'action' => 'created',
        ]);
    }

    public function test_audit_log_on_update()
    {
        $cert = Certificate::factory()->create(['name' => 'Old']);
        $cert->update(['name' => 'New']);

        $log = ActivityLog::where('subject_id', $cert->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Old', $log->changes['before']['name']);
        $this->assertEquals('New', $log->changes['after']['name']);
    }

    public function test_audit_log_on_force_delete()
    {
        $cert = Certificate::factory()->create(['name' => 'Test']);
        $cert->delete();
        $cert->forceDelete();

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Certificate::class,
            'subject_id' => $cert->id,
            'action' => 'deleted',
        ]);
    }
}
