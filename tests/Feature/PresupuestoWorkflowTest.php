<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Presupuesto;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PresupuestoWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_presupuesto()
    {
        $presupuestoData = [
            'doctor_id' => 1, // Assuming a patient with ID 1 exists
            'patient_id' => 1, // Assuming a patient with ID 1 exists
            'amount' => $this->faker->randomFloat(2, 100, 1000), // Random amount between 100 and 1000
            'description' => $this->faker->sentence,
            // 'location_id' => $this->location->id,
        ];

        $response = $this->postJson('/presupuesto/store', $presupuestoData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Presupuesto created successfully'
            ]);
    }
}
