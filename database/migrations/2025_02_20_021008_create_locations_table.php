<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 250);
            $table->string('avatar', 250);
            $table->string('city', 250);
            $table->string('state', 250);
            $table->string('zip', 50);
            $table->text('address')->nullable();
            $table->string('email', 150);
            $table->string('phone1', 150);
            $table->string('phone2', 150);

            
            // Provider IDs
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            // $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('client_id')->references('id')->on('patients')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
