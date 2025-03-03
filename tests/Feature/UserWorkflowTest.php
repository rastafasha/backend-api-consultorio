<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class UserWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_user()
    {
        $userData = User::factory()->make()->toArray(); // Use factory to create user data
        // $userData['password'] = 'password'; // Set password

        $response = $this->postJson('/users/store', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'User created successfully'
            ]);
    }
}
