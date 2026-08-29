<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * After the test database is migrated, ensure the demo admin
     * exists. The seeder (`DatabaseSeeder::seedUser`) uses
     * `updateOrCreate(['email' => 'demo@jarir.test'])` which is
     * idempotent — running it here is a no-op if the row already
     * exists.
     *
     * The /admin/* routes + login form both expect this user to
     * be present in the database. Without it, every test that
     * does `actingAs(['email' => 'demo@jarir.test'])` (or uses
     * the actual login flow in feature tests) silently logs in
     * as a non-existent user and gets 403s.
     *
     * We guard the table existence so tests that don't migrate
     * the database (e.g. Phase0RoutesTest, which just exercises
     * the route loader) still pass.
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
            $this->ensureDemoUserExists();
        }
    }

    protected function ensureDemoUserExists(): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'demo@jarir.test'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin',
            ],
        );
    }
}
