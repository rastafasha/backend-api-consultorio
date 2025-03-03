<?php

namespace Database\Factories\Doctor;

use App\Models\Doctor\Specialitie;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialitieFactory extends Factory
{
    protected $model = Specialitie::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'state' => $this->faker->randomElement([1, 2])
        ];
    }
}
