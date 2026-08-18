<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('certificates', 'tasks');

        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
            $table->dropColumn('price');
            $table->dropColumn('status');
            $table->dropColumn('expires_at');
            $table->string('executor')->nullable()->after('title');
            $table->date('due_date')->nullable()->after('executor');
            $table->boolean('completed')->default(false)->after('due_date');
            $table->index('completed');
        });

        DB::table('activity_logs')
            ->where('subject_type', 'App\Models\Certificate')
            ->update(['subject_type' => 'App\Models\Task']);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['completed']);
            $table->dropColumn('executor');
            $table->dropColumn('due_date');
            $table->dropColumn('completed');
            $table->renameColumn('title', 'name');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('status')->default('active')->nullable();
            $table->date('expires_at')->nullable();
        });

        Schema::rename('tasks', 'certificates');
    }
};
