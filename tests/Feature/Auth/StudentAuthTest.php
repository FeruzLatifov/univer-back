<?php

namespace Tests\Feature\Auth;

use App\Models\EStudent;
use App\Models\SystemLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake Redis/cache interactions used by rate limiter
        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('cache.stores.array', [
            'driver' => 'array',
        ]);

        $this->app['config']->set('database.default', env('DB_CONNECTION', 'pgsql'));
        $this->app['config']->set('database.connections.testing', config('database.connections.' . config('database.default')));
    }

    /** @test */
    public function student_can_login_and_receive_refresh_token()
    {
        $student = EStudent::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => $student->student_id_number,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'student_id_number'],
                    'access_token',
                    'refresh_token',
                ],
            ]);

        $this->assertDatabaseCount('auth_refresh_tokens', 1);
        $this->assertDatabaseHas('auth_refresh_tokens', [
            'user_id' => $student->id,
            'user_type' => 'student',
        ]);
    }

    /** @test */
    public function rate_limiter_blocks_after_max_attempts()
    {
        $student = EStudent::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $this->app['config']->set('AUTH_MAX_ATTEMPTS', 2);
        $this->app['config']->set('AUTH_LOCKOUT_MINUTES', 10);

        // First attempt (fail)
        $this->postJson('/api/v1/student/auth/login', [
            'student_id' => $student->student_id_number,
            'password' => 'wrong',
        ])->assertStatus(401);

        // Second attempt (fail)
        $this->postJson('/api/v1/student/auth/login', [
            'student_id' => $student->student_id_number,
            'password' => 'wrong',
        ])->assertStatus(401);

        // Third attempt should be rate limited
        $this->postJson('/api/v1/student/auth/login', [
            'student_id' => $student->student_id_number,
            'password' => 'wrong',
        ])->assertStatus(429)
          ->assertJson([
              'success' => false,
          ]);
    }

    /** @test */
    public function refresh_token_endpoint_returns_new_tokens()
    {
        $student = EStudent::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $loginResponse = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => $student->student_id_number,
            'password' => 'secret123',
        ])->json('data');

        $refreshResponse = $this->postJson('/api/v1/student/auth/refresh', [
            'refresh_token' => $loginResponse['refresh_token'],
        ], [
            'Authorization' => 'Bearer ' . $loginResponse['refresh_token'],
        ]);

        $refreshResponse->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'refresh_token',
                ],
            ]);

        $this->assertEquals(2, DB::table('auth_refresh_tokens')->count());
    }
}

