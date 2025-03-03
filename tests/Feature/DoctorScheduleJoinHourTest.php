<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Doctor\DoctorScheduleDay;
use App\Models\Doctor\DoctorScheduleHour;
use App\Models\Doctor\DoctorScheduleJoinHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DoctorScheduleJoinHourTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_doctor_schedule_join_hour()
    {
        $scheduleDay = DoctorScheduleDay::factory()->create(); // Create a schedule day

        $doctorScheduleHour = DoctorScheduleHour::factory()->create(); // Create a schedule hour
        $joinHourData = [
            'doctor_schedule_day_id' => $scheduleDay->id,
            'doctor_schedule_hour_id' => $doctorScheduleHour->id, // Use the created schedule hour ID
        ];

        $joinHour = DoctorScheduleJoinHour::create($joinHourData);

        $this->assertDatabaseHas('doctor_schedule_join_hours', [
            'id' => $joinHour->id,
            'doctor_schedule_day_id' => $scheduleDay->id,
            'doctor_schedule_hour_id' => $doctorScheduleHour->id, // Use the created schedule hour ID
        ]);
    }
}
