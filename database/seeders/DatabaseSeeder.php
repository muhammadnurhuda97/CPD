<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jalankan migrate otomatis
        Artisan::call('migrate', [
            '--force' => true, // force untuk production / CI/CD
        ]);

        // Jalankan seeder lainnya
        $this->call([
            AdminUserSeeder::class,
            UserSeeder::class,
        ]);
    }
}