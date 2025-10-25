<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Mengecek apakah user admin sudah ada, jika tidak maka tambah
        if (DB::table('users')->where('email', 'admin@admin.com')->doesntExist()) {
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => 'admin@pemudadigital.com',
                'password' => Hash::make('Semangat1M'), // Ganti password sesuai kebutuhan
                'whatsapp' => '',
                'role' => 'admin',
                'username' => 'admin',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
