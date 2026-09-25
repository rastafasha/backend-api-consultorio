<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinicas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('subdominio')->unique(); // ej: clinicasanitas (para ://klyntic.com)
            $table->enum('tipoClinica', ['consultorio', 'clinica'])->default('consultorio'); // El switch maestro
            $table->string('direccion_unica')->nullable();
            $table->string('banner_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('status')->default('activo');
            $table->timestamps();
            $table->softDeletes(); // Borrado suave premium
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinicas');
    }
};
