<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Admin RH',
            'last_name_p' => 'Sistema',
            'last_name_m' => 'Principal',
            'email' => 'rh@empresa.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::RH,
            'is_active' => true
        ]);

        User::create([
            'name' => 'Jefe Planeación',
            'last_name_p' => 'Proyectos',
            'last_name_m' => 'Organización',
            'email' => 'planeacion@empresa.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::PLANNING,
            'is_active' => true
        ]);

        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "Desarrollador $i",
                'last_name_p' => 'Dev',
                'last_name_m' => 'López',
                'email' => "desarrollador$i@empresa.com",
                'password' => Hash::make('password123'),
                'role_id' => Role::DEVELOPER,
                'is_active' => true
            ]);
        }
        //Tester
        for ($i = 1; $i <= 2; $i++) {
            User::create([
                'name' => "Tester $i",
                'last_name_p' => 'QA',
                'last_name_m' => 'Testing',
                'email' => "tester$i@empresa.com",
                'password' => Hash::make('password123'),
                'role_id' => Role::TESTER,
                'is_active' => true
            ]);
        }
    }
}
