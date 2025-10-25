<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Muhammad Nur Huda',
            'email' => 'muhammadnurhuda97@gmail.com',
            'password' => Hash::make('11223344'), // Ganti jika perlu
            'whatsapp' => '082245342997',
            'role' => 'user',
            'username' => 'muhammadnurhuda97',
        ]);
    }
}
