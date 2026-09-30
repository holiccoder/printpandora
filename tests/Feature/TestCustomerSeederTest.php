<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestCustomerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestCustomerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_customer_seeder_repairs_existing_account_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('wrong-password'),
            'email_verified_at' => null,
        ]);

        $this->seed(TestCustomerSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password', (string) $user->getRawOriginal('password')));
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Auth::guard('web')->attempt([
            'email' => 'test@example.com',
            'password' => 'password',
        ]));
    }
}
