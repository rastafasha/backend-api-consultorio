<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClinicaIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('clinica_id')
              ->nullable()
              ->after('status') // Se coloca limpiamente debajo de la columna status [2]
              ->constrained('clinicas')
              ->onDelete('set null'); // Si una clínica se borra, el usuario no se elimina
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['clinica_id']);
        $table->dropColumn('clinica_id');
    });
}
}
