<?php

namespace Database\Factories\Patient;

use App\Models\User;
use App\Models\Patient\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Buscamos un doctor, si no existe, creamos uno al vuelo para que no falle el seeder
        $doctor = User::role('DOCTOR')->inRandomOrder()->first() ?? User::factory();

        return [
            "name" => $this->faker->firstName(), // Mejor usar firstName para 'name'
            "surname" => $this->faker->lastName(),
            "user_id" => null,          // Empieza en null hasta que el paciente se registre en la App
            "phone" => $this->faker->phoneNumber(),
            "email" => $this->faker->unique()->safeEmail(),
            "birth_date" => $this->faker->dateTimeBetween("1985-10-01", "2000-10-25"),
            "gender" => $this->faker->randomElement([1, 2]),
            "education" => $this->faker->word(),
            "address" => $this->faker->address(),
            "antecedent_family" => $this->faker->text(300),
            "antecedent_personal" => $this->faker->text(200),
            "antecedent_alerg" => $this->faker->text(150),
            "current_desease" => $this->faker->text(100),
            "n_doc" => (string) $this->faker->unique()->numberBetween(1000000, 99999999),
            "created_at" => $this->faker->dateTimeBetween("2023-01-01", "2023-12-25"),
        ];
    }
}
