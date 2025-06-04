<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->slug,
            'label' => $this->faker->sentence,
            'icon' => 'file-text',
            'singleton' => false,
            'schema' => [],
        ];
    }
}
