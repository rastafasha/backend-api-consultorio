<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinica_medico', function (Blueprint $table) {
            $table->id();
            
            // Relación con tu tabla de clínicas (CRM/Organizaciones)
            $table->foreignId('clinica_id')
                  ->constrained('clinicas') // Asegúrate de que coincida con el nombre real de tu tabla de clínicas
                  ->onDelete('cascade');
                  
            // Relación con tu tabla de usuarios (Médicos)
            $table->foreignId('medico_id')
                  ->constrained('users') // Mapeado a tu tabla 'users' actual
                  ->onDelete('cascade');

            $table->timestamps();

            // Índice compuesto único para evitar duplicaciones a nivel de base de datos
            $table->unique(['clinica_id', 'medico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinica_medico');
    }
};
