<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('title');
            $table->string('executor')->nullable()->after('title');
            $table->date('due_date')->nullable()->after('executor');
            $table->boolean('completed')->default(false)->after('due_date');
            $table->index('completed');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['completed']);
            $table->dropColumn('executor');
            $table->dropColumn('due_date');
            $table->dropColumn('completed');
        });
    }
};
