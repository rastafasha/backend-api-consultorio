<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('referencia', 250);
            $table->string('metodo', 250);
            $table->string('bank_name', 250);
            $table->double('monto', 250);
            $table->string('nombre', 250);
            $table->string('email', 250);
            $table->string('image', 250)->nullable();
            $table->timestamp('fecha');
            $table->enum('status', [
                'APPROVED',
                'PENDING',
                'REJECTED'
            ])->default('PENDING');


            // Provider IDs
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
