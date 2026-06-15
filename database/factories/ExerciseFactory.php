<?php

namespace Database\Factories;

use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;

        $muscleGroups = [
            'Chest', 'Back', 'Shoulders', 'Biceps', 'Triceps',
            'Quadriceps', 'Hamstrings', 'Glutes', 'Calves', 'Core',
        ];

        $equipmentTypes = [
            'Barbell', 'Dumbbell', 'Kettlebell', 'Cable Machine',
            'Resistance Band', 'Bodyweight', 'Machine',
        ];

        return [
            'name'           => 'Exercise ' . $counter++,
            'muscle_group'   => $this->faker->randomElement($muscleGroups),
            'equipment_type' => $this->faker->optional()->randomElement($equipmentTypes),
            'video_url'      => $this->faker->optional()->url(),
            'description'    => $this->faker->optional()->sentence(),
        ];
    }
}
