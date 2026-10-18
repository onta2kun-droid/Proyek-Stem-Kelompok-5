<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@knowos.my.id'],
            [
                'name'     => 'Admin Kelompok 6',
                'password' => Hash::make('prot-hidro-2026'),
            ]
        );
    }
}
