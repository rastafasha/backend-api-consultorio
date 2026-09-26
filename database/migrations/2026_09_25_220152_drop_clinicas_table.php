<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropClinicasTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Desactivamos temporalmente las llaves foráneas para evitar bloqueos al borrar
        Schema::disableForeignKeyConstraints();

        // 1. Eliminamos las tablas que ya no se van a usar en Laravel
        Schema::dropIfExists('clinica_medico');
        Schema::dropIfExists('clinicas');

        // Reaktivamos las restricciones
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // En una migración destructiva SaaS no es necesario un rollback complejo de tablas eliminadas
    }
}