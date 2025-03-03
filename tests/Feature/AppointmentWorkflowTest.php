<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient\Patient;
use App\Models\Doctor\Specialitie;
use Spatie\Permission\Models\Role;
use App\Models\Appointment\Appointment;
use App\Models\Doctor\DoctorScheduleDay;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Doctor\DoctorScheduleJoinHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_appointment()
    {
        $doctorRole = Role::firstOrCreate(['name' => 'DOCTOR']);
        $doctor = User::whereHas('roles', function($q) use ($doctorRole) {
            $q->where('name', $doctorRole->name);
        })->first();

        if (!$doctor) {
            $doctor = User::factory()->create(); // Create a doctor if none exists
            $doctor->assignRole($doctorRole); // Assign the doctor role
        }

        $speciality = Specialitie::factory()->create(); // Create a speciality if none exists
        $date_appointment = $this->faker->dateTimeBetween("2023-01-01 00:00:00", "2023-12-25 23:59:59");
        $patient = Patient::factory()->create(); // Create a patient
        
        $doctor_schedule_day = DoctorScheduleDay::factory()->create(['user_id' => $doctor->id]); // Create a schedule day for the doctor
        $doctor_schedule_join_hour = DoctorScheduleJoinHour::factory()->create(['doctor_schedule_day_id' => $doctor_schedule_day->id]); // Create a join hour for the schedule day
        
        $status = $this->faker->randomElement([1, 2]); // Initialize the status variable

        $appointment = [
            "doctor_id" => $doctor->id,
            "n_doc" => $this->faker->numerify('########'),
            "name" => $this->faker->firstName,
            "surname" => $this->faker->lastName,
            "phone" => $this->faker->phoneNumber,
            "patient_id" => $patient->id, // Use the created patient's ID
            "date_appointment" => $date_appointment->format('Y-m-d H:i:s'),
            "speciality_id" => $speciality->id, // Use the created speciality ID
            "doctor_schedule_join_hour_id" => $doctor_schedule_join_hour->id, // Use the created join hour ID
            "user_id" => User::all()->random()->id,
            "amount" => $this->faker->randomElement([100,150,200,250,80,120,95,75,160,230,110]),
            "status" => $status,
            "status_pay" => $this->faker->randomElement([1, 2]),
            "date_attention" => $status == 2 ? $this->faker->dateTimeBetween($date_appointment, "2023-12-25 23:59:59") : NULL,
        ];


        // \Log::info('Appointment:', ['id' => $appointment]);

        $response = $this->postJson('/appointments/store', $appointment);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Appointment created successfully'
            ]);
    }
}
