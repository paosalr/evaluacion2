<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rhRole = Role::where('name', 'RH')->first();
        $planningRole = Role::where('name', 'Planeacion')->first();
        $developerRole = Role::where('name', 'Desarrollador')->first();
        $testerRole = Role::where('name', 'Tester')->first();

        User::create([
           'name' => 'Admin',
           'last_name_p' => 'RH',
           'last_name_m' => 'RH',
           'email' => 'rh@prueba.com',
           'password' => Hash::make('password'),
           'registration_date' => now(),
            'role_id' => $rhRole->id,
        ]);

        User::create([
            'name' => 'Planeacion',
            'last_name_p' => 'plan',
            'last_name_m' => 'plan',
            'email' => 'planning@prueba.com',
            'password' => Hash::make('password'),
            'registration_date' => now(),
            'role_id' => $planningRole->id,
        ]);

        for ($i = 1; $i <= 5; $i++){
            User::create([
               'name' => 'Desarrollador' . $i,
               'last_name_p' => 'devep',
                'last_name_m' => 'devep',
                'email' => 'developer' . $i . '@prueba.com',
               'password' => Hash::make('password'),
               'registration_date' => now(),
               'role_id' => $developerRole->id,
            ]);
        }

        for ($i = 1; $i <= 2; $i++){
            User::create([
                'name' => 'Tester' . $i,
                'last_name_p' => 'test',
                'last_name_m' => 'test',
                'email' => 'tester' . $i . '@prueba.com',
                'password' => Hash::make('password'),
                'registration_date' => now(),
                'role_id' => $testerRole->id,
            ]);
        }
    }
}
