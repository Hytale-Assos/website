<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo accounts.
     *
     * Demo credentials never run outside local and testing: a verified
     * demo member in production would be a live backdoor.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Internal member: registered with a school email, identity fully
        // anchored (school email verified at "registration").
        $internal = User::create([
            'name' => 'Interne Démo',
            'email' => 'internal@myskolae.fr',
            'password' => 'password',
            'school_email' => 'internal@myskolae.fr',
        ]);
        $internal->forceFill([
            'is_internal' => true,
            'is_external' => false,
            'email_verified_at' => now(),
        ])->save();

        // External member: no school anchor, status frozen as external.
        $external = User::create([
            'name' => 'Externe Démo',
            'email' => 'external@example.com',
            'password' => 'password',
        ]);
        $external->forceFill([
            'is_internal' => false,
            'is_external' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}
