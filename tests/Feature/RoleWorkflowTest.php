<?php

namespace Tests\Feature;

use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class RoleWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_role()
    {
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

        $response = $this->postJson('/api/roles/store', $roles);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Role created successfully'
            ]);
    }

    public function test_can_list_roles()
    {
        Role::factory()->count(5)->create();

        $response = $this->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description'],
                ],
            ]);
    }
}
