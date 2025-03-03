<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BasicTest extends TestCase
{
    // This trait provides a fresh database for each test
    // It will use the database configuration specified in .env.testing
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }


    /**
     * Test if the API is returning proper JSON structure
     */
    public function test_api_returns_proper_json()
    {
        $response = $this->getJson('/api/ping');
        
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'pong']);
    }

}