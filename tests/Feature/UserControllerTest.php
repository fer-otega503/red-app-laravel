<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user creation with all fields, including phone_number and email.
     */
    public function test_store_user_successfully_with_phone_number_and_email(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '+50371234567',
        ];

        // Send a post request to the user store API route
        $response = $this->post(route('api.user.store'), $userData);

        $response->assertStatus(201); // Created
        $response->assertJsonPath('data.name', 'John Doe');
        $response->assertJsonPath('data.email', 'john.doe@example.com');
        $response->assertJsonPath('data.phone_number', '+50371234567');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '+50371234567',
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test user creation with a phone_number but without email (null/missing email).
     */
    public function test_store_user_successfully_with_phone_number_and_no_email(): void
    {
        $userData = [
            'name' => 'Jane Doe',
            'phone_number' => '+50371234568',
        ];

        // Send post request to store API route (without email)
        $response = $this->post(route('api.user.store'), $userData);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Jane Doe');
        $response->assertJsonPath('data.email', null);
        $response->assertJsonPath('data.phone_number', '+50371234568');

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => null,
            'phone_number' => '+50371234568',
        ]);

        $user = User::where('phone_number', '+50371234568')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test that user creation fails validation and returns JSON with 422 status
     * when phone_number is missing, even when the Accept header is not set.
     */
    public function test_store_user_fails_validation_and_returns_json_422_when_phone_number_is_missing(): void
    {
        $userData = [
            'name' => 'No Phone User',
            'email' => 'nophone@example.com',
            // 'phone_number' is missing
        ];

        // Send post request WITHOUT 'Accept: application/json' header
        $response = $this->post(route('api.user.store'), $userData, [
            // Do not send Accept header to check the forced JSON configuration
        ]);

        // Instead of redirecting to home/prev page (302), it should return JSON (422)
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number']);
    }
}
