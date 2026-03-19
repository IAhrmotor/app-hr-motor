<?php

namespace Database\Factories;

use App\Models\Dealership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dealership>
 */
class DealershipFactory extends Factory
{
    protected $model = Dealership::class;

    public function definition(): array
    {
        $city = fake()->unique()->city();

        return [
            'name' => $city,
            'salesforce_id' => 'DLR-' . fake()->unique()->bothify('####'),
            'image_path' => null,
            'phone' => fake()->phoneNumber(),
            'google_maps_url' => fake()->url(),
            'reviews_url' => fake()->url(),
        ];
    }
}
