<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Doctor\DoctorScheduleDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DoctorScheduleDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_doctor_schedule_day()
    {
        $doctor = User::factory()->create(); // Create a doctor user

        $scheduleDayData = [
            'user_id' => $doctor->id,
            'day' => 'Lunes',
        ];

        $scheduleDay = DoctorScheduleDay::create($scheduleDayData);

        $this->assertDatabaseHas('doctor_schedule_days', [
            'id' => $scheduleDay->id,
            'user_id' => $doctor->id,
            'day' => 'Lunes',
        ]);
    }
}
