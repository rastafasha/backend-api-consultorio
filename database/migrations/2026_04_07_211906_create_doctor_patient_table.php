<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorPatientTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('doctor_patient', function (Blueprint $table) {
        $table->id();
        $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->timestamps();
    });
        // 2. PASAMOS LOS DATOS ACTUALES
        // $patients = DB::table('patients')->whereNotNull('doctor_id')->get();
        // foreach ($patients as $patient) {
        //     DB::table('doctor_patient')->insert([
        //         'doctor_id' => $patient->doctor_id,
        //         'patient_id' => $patient->id,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);
        // }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctor_patient');
    }
}
