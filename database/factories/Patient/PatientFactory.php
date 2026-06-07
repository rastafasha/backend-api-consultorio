<?php

namespace Database\Factories\Patient;

use App\Models\User;
use App\Models\Patient\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;


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
            "name" => $this->faker->firstName(),
            "surname" => $this->faker->lastName(),

            // Al generar pruebas, asumimos que pertenecen al administrador o médico inicial (ID 1)
            // Esto evita que tus comandos cronjobs de prueba fallen por campos nulos
            "user_id" => 1,
            "mongo_user_id" => "1", // Apunta al documento con _id: "1" en klyntic_consultorios (Mongo)

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
