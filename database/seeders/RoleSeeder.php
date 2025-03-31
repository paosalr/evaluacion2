<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'id' => Role::RH,
                'name' => 'RH'
            ],
            [
                'id' => Role::DEVELOPER,
                'name' => 'Desarrollador'
            ],
            [
                'id' => Role::PLANNING,
                'name' => 'Planeación'
            ],
            [
                'id' => Role::TESTER,
                'name' => 'Tester'
            ]
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                $role
            );
        }
    }
}
