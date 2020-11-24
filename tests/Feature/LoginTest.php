<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;

/**
 * Class LoginTest
 * @package Tests\Feature
 */
class LoginTest extends TestCase
{
    /**
     * testLoginMissingFields
     * Test sending request without data
     *
     * Expect failure with 422
     */
    public function testLoginMissingFields()
    {
        $this->json('POST', 'api/user/login')
            ->assertStatus(422);
    }
    
    /**
     * testLoginUnauthorized
     * Test sending request with inexistent credentials
     *
     * Expect failure with 401
     */
    public function testLoginUnauthorized()
    {
        $payload = [
            'email' => 'nonexistent@email.com',
            'password' => 'wrongpassword'
        ];

        $this->json('POST', 'api/user/login', $payload)
            ->assertStatus(401);
    }
    
    /**
     * testLoginSuccessfully
     * Test creating new user and logging with these credentials. Delete afterwards.
     *
     * Expect success with 200
     */
    public function testLoginSuccessfully()
    {
        $user = factory(User::class)->create([
            'email' => 'testing@email.com',
            'password' => bcrypt('password'),
        ]);

        $payload = [
            'email' => 'testing@email.com',
            'password' => 'password',
        ];

        $this->json('POST', 'api/user/login', $payload)
            ->assertStatus(200);

        $user->delete();
    }
}
