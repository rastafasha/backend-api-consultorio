<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 250);
            $table->string('surname', 250);
            $table->string('email', 250)->nullable();
            $table->string('n_doc')->nullable();
            $table->string('phone', 25)->nullable();
            
            $table->text('address')->nullable();
            $table->tinyInteger('gender')->default(1);
            $table->timestamp('birth_date')->nullable();
            $table->string('avatar')->nullable();
            $table->string('education', 150)->nullable();
            $table->text('antecedent_family')->nullable();
            $table->text('antecedent_personal')->nullable();
            $table->text('antecedent_alerg')->nullable();
            $table->text('current_desease')->nullable();
            $table->string('ta', 25)->nullable();
            $table->string('fc', 25)->nullable();
            $table->string('fr', 25)->nullable();
            $table->string('temperature', 25)->nullable();
            $table->string('peso', 250)->nullable();
            
            // Provider IDs
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();


            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            // $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients');
    }
}
