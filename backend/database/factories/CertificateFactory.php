<?php

namespace Database\Factories;

use App\Models\Certificate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 1, 10000),
            'expires_at' => now()->addDays(fake()->numberBetween(1, 365))->toDateString(),
            'status' => fake()->randomElement(['active', 'expired', 'redeemed']),
        ];
    }
}
