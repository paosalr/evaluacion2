<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            [
                'id' => TaskStatus::PENDING_ASSIGNMENT,
                'name' => 'En espera de asignación',
            ],
            [
                'id' => TaskStatus::IN_PROGRESS,
                'name' => 'En proceso',
            ],
            [
                'id' => TaskStatus::IN_TESTING,
                'name' => 'En pruebas',
            ],
            [
                'id' => TaskStatus::BUG,
                'name' => 'Bug',
            ],
            [
                'id' => TaskStatus::FINISHED,
                'name' => 'Finalizada',
            ]
        ];

        foreach ($statuses as $status) {
            TaskStatus::create($status);
        }
    }
}
