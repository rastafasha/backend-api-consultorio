<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointment_pays', function (Blueprint $table) {
            $table->bigIncrements('id');
        
            $table->string('method_payment', 150);
            $table->double('amount')->nullable();

            
            // Provider IDs
            $table->unsignedBigInteger('appointment_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            // $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointment_pays');
    }
}
