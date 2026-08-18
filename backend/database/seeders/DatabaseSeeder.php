<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        Task::firstOrCreate(
            ['title' => 'Подготовить отчёт за неделю'],
            [
                'executor' => 'Иван Петров',
                'due_date' => now()->addDays(3)->toDateString(),
                'completed' => false,
            ]
        );

        Task::firstOrCreate(
            ['title' => 'Проверить новые заявки'],
            [
                'executor' => 'Мария Сидорова',
                'due_date' => now()->addDays(1)->toDateString(),
                'completed' => false,
            ]
        );

        Task::firstOrCreate(
            ['title' => 'Обновить документацию'],
            [
                'executor' => 'Алексей Иванов',
                'due_date' => now()->addDays(7)->toDateString(),
                'completed' => true,
            ]
        );
    }
}
