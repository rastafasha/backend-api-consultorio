<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRlaboratoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('r_laboratories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name_file', 250)->nullable();
            $table->text('comentario')->nullable();
            $table->string('size', 50)->nullable();
            $table->string('resolution', 50)->nullable();
            $table->string('file', 250)->nullable();
            $table->string('type', 50)->nullable();

            
            // Provider IDs
            $table->unsignedBigInteger('patient_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('r_laboratories');
    }
}
