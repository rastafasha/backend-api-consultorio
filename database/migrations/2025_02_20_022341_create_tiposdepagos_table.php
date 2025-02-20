<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTiposdepagosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tiposdepagos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tipo', 250);
            $table->string('ciorif', 250)->nullable();
            $table->string('telefono', 250)->nullable();
            $table->string('bankAccount', 250)->nullable();
            $table->string('bankName', 250)->nullable();
            $table->string('bankAccountType', 250)->nullable();
            $table->string('email', 250)->nullable();
            $table->string('user', 250)->nullable();
            $table->enum('status', [
                'ACTIVE', 'INACTIVE'
                ])->default('INACTIVE');

            
            $table->unsignedBigInteger('user_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tiposdepagos');
    }
}
