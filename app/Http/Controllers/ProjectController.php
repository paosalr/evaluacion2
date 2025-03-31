<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectStatus;
use Illuminate\Validation\Rule;
class ProjectController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = Project::with(['status', 'assignedUsers'])
            ->orderBy('created_at', 'desc');

        if ($user->role->name !== 'Planeación') {
            $query->whereHas('assignedUsers', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(10)
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:projects',
            'description' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $project = Project::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => ProjectStatus::PLANNING
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proyecto creado exitosamente.',
                'data' => $project->load('status')
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proyecto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignUsers(Request $request, $projectId)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => [
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if (!$user || !in_array($user->role->name, ['Desarrollador', 'Tester'])) {
                        $fail('El usuario debe ser desarrollador o tester');
                    }
                }
            ]
        ]);

        $project = Project::findOrFail($projectId);
        $project->assignedUsers()->sync($request->user_ids);

        return response()->json([
            'success' => true,
            'message' => 'Usuarios asignados correctamente'
        ]);
    }

    public function show($id)
    {
        $project = Project::with('developers')->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        // Verificar permisos para ver el proyecto
        $user = auth()->user();
        $canView = $project->status === 'Planeación' ||
            $project->developers->contains($user->id) ||
            strtolower($user->role->name) === 'planeación';

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver este proyecto.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:projects,name,' . $id,
            'description' => 'sometimes|string'
        ]);

        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        if (strtolower(auth()->user()->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede actualizar proyectos.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $project->update($request->only(['name', 'description']));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proyecto actualizado exitosamente.',
                'data' => $project
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el proyecto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validStatuses = ProjectStatus::pluck('id')->toArray();

        $request->validate([
            'status_id' => [
                'required',
                'integer',
                Rule::in($validStatuses)
            ]
        ]);

        $project = Project::findOrFail($id);

        if (strtolower(auth()->user()->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede cambiar el estado del proyecto.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $project->status_id = $request->status_id;
            $project->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estado del proyecto actualizado exitosamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado del proyecto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (strtolower(auth()->user()->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede eliminar proyectos.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $project = Project::findOrFail($id);
            $project->tasks()->each(function($task) {
                $task->assignedUsers()->detach();
                $task->delete();
            });

            $project->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proyecto y sus tareas asociadas eliminados exitosamente.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proyecto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignDevelopers(Request $request, $id)
    {
        $request->validate([
            'developer_ids' => 'required|array',
            'developer_ids.*' => 'exists:users,id,role_id,' . Role::where('name', 'Desarrollador')->first()->id
        ]);

        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        if (strtolower(auth()->user()->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede asignar desarrolladores.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $project->developers()->sync($request->developer_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Desarrolladores asignados exitosamente al proyecto.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar desarrolladores.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTasksByProject($id)
    {
        $project = Project::with('tasks')->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        // Verificar permisos
        $user = auth()->user();
        $canView = $project->status === 'Planeación' ||
            $project->developers->contains($user->id) ||
            in_array(strtolower($user->role->name), ['planeación', 'tester']);

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver las tareas de este proyecto.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $project->tasks
        ]);
    }

    public function getProjectTasks($projectId)
    {
        try {
            $project = Project::with('status')->findOrFail($projectId);
            $user = auth()->user();

            // Verificar permisos
            if (strtolower($user->role->name) === 'rh') {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado para RH'
                ], 403);
            }

            $isAssigned = $project->assignedUsers()->where('user_id', $user->id)->exists() ||
                strtolower($user->role->name) === 'planeación';

            if (!$isAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver las tareas de este proyecto'
                ], 403);
            }

            $tasks = Task::where('project_id', $projectId)
                ->with(['status', 'assignedUsers:id,name,last_name_p,last_name_m'])
                ->get()
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status->name,
                        'assigned_users' => $task->assignedUsers->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'name' => "{$user->name} {$user->last_name_p} {$user->last_name_m}"
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'status' => $project->status->name
                    ],
                    'tasks' => $tasks
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las tareas del proyecto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getProjectsByStatus($status)
    {
        $user = auth()->user();
        $validStatuses = ['Planeación', 'En desarrollo', 'Finalizado', 'Cancelado', 'En pausa'];

        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de proyecto no válido.'
            ], 400);
        }

        $query = Project::where('status', $status);

        if (strtolower($user->role->name) !== 'planeación') {
            $query->whereHas('developers', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    public function getAssignableDevelopers()
    {
        if (strtolower(auth()->user()->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede ver desarrolladores asignables.'
            ], 403);
        }

        $developers = User::whereHas('role', function($q) {
            $q->where('name', 'Desarrollador');
        })->where('is_active', true)
            ->select('id', 'name', 'last_name_p', 'last_name_m', 'email')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $developers
        ]);
    }

    public function getProjectStatuses()
    {
        try {
            $statuses = [
                ['id' => ProjectStatus::IN_DEVELOPMENT, 'name' => 'En desarrollo'],
                ['id' => ProjectStatus::FINISHED, 'name' => 'Finalizado'],
                ['id' => ProjectStatus::CANCELLED, 'name' => 'Cancelado'],
                ['id' => ProjectStatus::PAUSED, 'name' => 'En pausa']
            ];

            return response()->json([
                'success' => true,
                'data' => $statuses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los estados de proyecto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
