<?php

namespace Database\Factories;

use App\Enums\TopUpStatus;
use App\Models\TopUpRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopUpRequest>
 */
class TopUpRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount_cents' => fake()->randomElement([20000, 50000, 75000, 100000, 150000]),
            'payment_reference' => 'TRF'.fake()->numerify('##########'),
            'note' => null,
            'status' => TopUpStatus::Pending,
        ];
    }
}
