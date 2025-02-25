<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingeneralsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settingenerals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 250)->nullable();
            $table->text('address');
            $table->string('email', 250)->nullable();
            $table->string('phone', 250)->nullable();
            $table->string('city', 250)->nullable();
            $table->string('state', 250)->nullable();
            $table->string('zip', 250)->nullable();
            $table->string('country', 250)->nullable();
            $table->string('avatar', 250)->nullable();
            
            // $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settingenerals');
    }
}
