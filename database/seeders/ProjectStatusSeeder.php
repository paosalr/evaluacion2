<?php

namespace Database\Seeders;

use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;

class ProjectStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            [
                'id' => ProjectStatus::PLANNING,
                'name' => 'Planeación',
            ],
            [
                'id' => ProjectStatus::IN_DEVELOPMENT,
                'name' => 'En desarrollo',
            ],
            [
                'id' => ProjectStatus::PAUSED,
                'name' => 'En pausa',
            ],
            [
                'id' => ProjectStatus::CANCELLED,
                'name' => 'Cancelado',
            ],
            [
                'id' => ProjectStatus::FINISHED,
                'name' => 'Finalizado',
            ]
        ];

        foreach ($statuses as $status) {
            ProjectStatus::create($status);
        }
    }
}
