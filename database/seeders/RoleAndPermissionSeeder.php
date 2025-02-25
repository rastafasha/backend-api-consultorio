<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        $permissions = [
        ['id' => 1, 'guard_name' => 'api','name' => 'patient_dashboard'],
        ['id' => 2, 'guard_name' => 'api','name' => 'admin_dashboard'],
        ['id' => 3, 'guard_name' => 'api','name' => 'doctor_dashboard'],
        ['id' => 4, 'guard_name' => 'api','name' => 'register_rol'],
        ['id' => 5, 'guard_name' => 'api','name' => 'list_rol'],
        ['id' => 6, 'guard_name' => 'api','name' => 'edit_rol'],
        ['id' => 7, 'guard_name' => 'api','name' => 'delete_rol'],

        ['id' => 8, 'guard_name' => 'api','name' => 'register_doctor'],
        ['id' => 9, 'guard_name' => 'api','name' => 'list_doctor'],
        ['id' => 10, 'guard_name' => 'api','name' => 'edit_doctor'],
        ['id' => 11, 'guard_name' => 'api','name' => 'delete_doctor'],
        ['id' => 12, 'guard_name' => 'api','name' => 'profile_doctor'],

        ['id' => 13, 'guard_name' => 'api','name' => 'register_patient'],
        ['id' => 14, 'guard_name' => 'api','name' => 'list_patient'],
        ['id' => 15, 'guard_name' => 'api','name' => 'edit_patient'],
        ['id' => 16, 'guard_name' => 'api','name' => 'delete_patient'],
        ['id' => 17, 'guard_name' => 'api','name' => 'profile_patient'],

        ['id' => 18, 'guard_name' => 'api','name' => 'register_staff'],
        ['id' => 19, 'guard_name' => 'api','name' => 'list_staff'],
        ['id' => 20, 'guard_name' => 'api','name' => 'edit_staff'],
        ['id' => 21, 'guard_name' => 'api','name' => 'delete_staff'],

        ['id' => 22, 'guard_name' => 'api','name' => 'register_appointment'],
        ['id' => 23, 'guard_name' => 'api','name' => 'list_appointment'],
        ['id' => 24, 'guard_name' => 'api','name' => 'edit_appointment'],
        ['id' => 25, 'guard_name' => 'api','name' => 'delete_appointment'],
        

        ['id' => 26, 'guard_name' => 'api','name' => 'register_specialty'],
        ['id' => 27, 'guard_name' => 'api','name' => 'list_specialty'],
        ['id' => 28, 'guard_name' => 'api','name' => 'edit_specialty'],
        ['id' => 29, 'guard_name' => 'api','name' => 'delete_specialty'],

        ['id' => 30, 'guard_name' => 'api','name' => 'show_payment'],
        ['id' => 31, 'guard_name' => 'api','name' => 'edit_payment'],
        ['id' => 32, 'guard_name' => 'api','name' => 'delete_payment'],
        ['id' => 33, 'guard_name' => 'api','name' => 'add_payment'],

        ['id' => 34, 'guard_name' => 'api','name' => 'activitie'],
        ['id' => 35, 'guard_name' => 'api','name' => 'calendar'],

        ['id' => 36, 'guard_name' => 'api','name' => 'expense_report'],
        ['id' => 37, 'guard_name' => 'api','name' => 'invoice_report'],
        ['id' => 38, 'guard_name' => 'api','name' => 'show_payment_cobros'],
        ['id' => 39, 'guard_name' => 'api','name' => 'show_payment_cobrar'],

        ['id' => 40, 'guard_name' => 'api','name' => 'settings'],
        ['id' => 41, 'guard_name' => 'api','name' => 'list_laboratory'],
        ['id' => 42, 'guard_name' => 'api','name' => 'edit_laboratory'],
        
        ['id' => 43, 'guard_name' => 'api','name' => 'list_publicidad'],
        ['id' => 44, 'guard_name' => 'api','name' => 'list_specialty_patient'],

        ['id' => 45, 'guard_name' => 'api','name' => 'register_location'],
        ['id' => 46, 'guard_name' => 'api','name' => 'list_location'],
        
        ['id' => 47, 'guard_name' => 'api','name' => 'view_patient'],
        ['id' => 48, 'guard_name' => 'api','name' => 'list_presupuesto'],
        ['id' => 49, 'guard_name' => 'api','name' => 'register_presupuesto'],

        ['id' => 50, 'guard_name' => 'api','name' => 'list_patient_doctor'],
        ['id' => 51, 'guard_name' => 'api','name' => 'cancel_appointment'],
        ['id' => 52, 'guard_name' => 'api','name' => 'view_notification'],
        ['id' => 53, 'guard_name' => 'api','name' => 'view_notification_appointment'],
        ['id' => 54, 'guard_name' => 'api','name' => 'view_notification_pagos'],
        ['id' => 55, 'guard_name' => 'api','name' => 'view_appointment'],
        ['id' => 56, 'guard_name' => 'api','name' => 'local_appointment'],
        ['id' => 57, 'guard_name' => 'api','name' => 'list_appointment_doctor'],
        ['id' => 58, 'guard_name' => 'api','name' => 'export_pdf'],
        ['id' => 59, 'guard_name' => 'api','name' => 'export_text'],
        ['id' => 60, 'guard_name' => 'api','name' => 'export_csv'],
        ['id' => 61, 'guard_name' => 'api','name' => 'export_xsl'],
        ['id' => 62, 'guard_name' => 'api','name' => 'edit_presupuesto'],
        ['id' => 63, 'guard_name' => 'api','name' => 'add_cita_doctor'],
        ['id' => 64, 'guard_name' => 'api','name' => 'pago_doctor'],
        ['id' => 65, 'guard_name' => 'api','name' => 'transferencia_doctor'],
        ['id' => 66, 'guard_name' => 'api','name' => 'payment_settings'],
        ['id' => 67, 'guard_name' => 'api','name' => 'list_presupuesto_doctor'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create roles
        $roles = [
            ['id' => 1, 'name' => 'SUPERADMIN', 'guard_name' => 'api'],
            ['id' => 2, 'name' => 'ADMIN', 'guard_name' => 'api'],
            ['id' => 3, 'name' => 'DOCTOR', 'guard_name' => 'api'],
            ['id' => 4, 'name' => 'RECEPCION', 'guard_name' => 'api'],
            ['id' => 5, 'name' => 'LABORATORIO', 'guard_name' => 'api'],
            ['id' => 6, 'name' => 'ASISTENTE', 'guard_name' => 'api'],
            ['id' => 7, 'name' => 'ENFERMERA', 'guard_name' => 'api'],
            ['id' => 8, 'name' => 'PERSONAL', 'guard_name' => 'api'],
            ['id' => 9, 'name' => 'GUEST', 'guard_name' => 'api'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        
        // Give all permissions to SUPERADMIN
        // $role3 = Role::create(['guard_name' => 'api','name' => 'SUPERADMIN']);
        $superadminRole = Role::find(1);
        
        $superadminRole->givePermissionTo(Permission::all());

        // Give all permissions to ADMIN
        $adminRole = Role::find(2);
        // $adminRole->givePermissionTo(Permission::all());

        // Assign specific permissions to other roles
        $doctorRole = Role::find(3);
        $recepcionRole = Role::find(4);
        $laboratorioRole = Role::find(5);
        $asistenteRole = Role::find(6);
        $enfermeraRole = Role::find(7);
        $guestRole = Role::find(9);
        // $personaladicionalRole = Role::find(5);

        // Assign permissions based on the provided SQL dump
        $adminRole->givePermissionTo([2,5,8,9,10,12,13.14,15,17,18,19,20,22,23,24,26,27,28,29,30,35,38,40,41,42,44,52,53,54]);
        $doctorRole->givePermissionTo([3,10,12,15,17,49,50,52,53,54,55,56,62,63,64,65,66]); // doctor specific permissions
        $guestRole->givePermissionTo([12,20]); // doctor specific permissions
        $recepcionRole->givePermissionTo([2,9,13,14,18,19,22,23,24,26,27,30,35,38,40,41,44]);
        $laboratorioRole->givePermissionTo([12,41,42]); // doctor specific permissions
        $asistenteRole->givePermissionTo([3,10,12,13,14,22,23,24,30,35,38]); // doctor specific permissions
        $enfermeraRole->givePermissionTo([13,14,22,23]); // doctor specific permissions

    }
}
