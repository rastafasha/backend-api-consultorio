<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePresupuestoItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('presupuesto_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name_medical')->nullable();
            $table->string('cantidad')->nullable();
            $table->double('precio', 250)->nullable();
            
            // Provider IDs
            $table->unsignedBigInteger('presupuesto_id')->nullable();


            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for provider relationships
            $table->foreign('presupuesto_id')->references('id')->on('presupuestos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('presupuesto_items');
    }
}
