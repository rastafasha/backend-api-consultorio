<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Patient\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PatientWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_patient()
    {
        $patientData = Patient::factory()->make()->toArray();
        $patientData = [
            'name' => $this->faker->name,
            'surname' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'gender' => 1,
            'birth_date' => $this->faker->date('Y-m-d', '-6 years'),
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'zip' => $this->faker->postcode,
            // 'location_id' => $this->location->id, // Assuming location is required
        ];

        $response = $this->postJson('/patients/store', $patientData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Patient created successfully'
            ]);
    }
}
