<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 🛡️ SANEADO MULTI-TENANT ENTERPRISE: Versión Agnóstica y Segura sin dependencias Doctrine
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Desactivamos restricciones para operar en caliente de forma segura
        Schema::disableForeignKeyConstraints();

        // 1. ELIMINACIÓN DE TABLAS OBSOLETAS (Limpieza SaaS Core)
        Schema::dropIfExists('clinica_medico');
        Schema::dropIfExists('clinicas');

        // 2. ALTERACIÓN DE LA TABLA 'users' (Inyectamos el Tenant ID)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Vamos directo a inyectar el string flexible para el ID NoSQL de MongoDB Atlas
                if (!Schema::hasColumn('users', 'clinica_id')) {
                    $table->string('clinica_id')->nullable()->after('status')->index();
                }
            });
        }

        // 3. ALTERACIÓN DE LA TABLA 'appointments'
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'clinica_id')) {
                    $table->string('clinica_id')->nullable()->index();
                }
            });
        }

        // 4. ALTERACIÓN DE LA TABLA 'patients'
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (!Schema::hasColumn('patients', 'clinica_id')) {
                    $table->string('clinica_id')->nullable()->index();
                }
            });
        }

        // 5. ALTERACIÓN DE LA TABLA 'appointment_pays'
        if (Schema::hasTable('appointment_pays')) {
            Schema::table('appointment_pays', function (Blueprint $table) {
                if (!Schema::hasColumn('appointment_pays', 'clinica_id')) {
                    $table->string('clinica_id')->nullable()->index();
                }
            });
        }

        // 6. ALTERACIÓN DE LA TABLA 'tiposdepagos'
        if (Schema::hasTable('tiposdepagos')) {
            Schema::table('tiposdepagos', function (Blueprint $table) {
                if (!Schema::hasColumn('tiposdepagos', 'clinica_id')) {
                    $table->string('clinica_id')->nullable()->index();
                }
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        //
    }
};