<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::create([
            'nama' => "Fahmi Idrus",
            'jabatan' => "IT",
            'username' => "fahmi07",
            'email' => "fahmiidrus131@gmail.com",
            'password' => Hash::make("Fahmi07"),
            'level' => 'admin', // atau otomatis 'pusat' / 'admin'
        ]);
    }
}
