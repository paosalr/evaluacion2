<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Task::with(['project', 'status', 'assignedUsers', 'createdBy']);

        // Planeación ve todas las tareas
        if (strtolower($user->role->name) === 'planeación') {
            $tasks = $query->paginate(10);
        }
        // Desarrolladores y Testers ven solo sus tareas asignadas
        else {
            $tasks = $query->where(function($q) use ($user) {
                $q->whereHas('assignedUsers', function($q) use ($user) {
                    $q->where('users.id', $user->id);
        })
                    ->orWhereHas('project', function($q) use ($user) {
                        $q->whereHas('assignedUsers', function($q) use ($user) {
                            $q->where('users.id', $user->id);
                        });
                    });
            })->paginate(10);
        }

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'assigned_users' => 'required|array|min:1',
            'assigned_users.*' => [
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if (!$user || !in_array($user->role->name, ['Desarrollador', 'Tester'])) {
                        $fail('Solo se pueden asignar Desarrolladores o Testers a tareas.');
                    }
                }
            ]
        ]);

        if (strtolower(auth()->user()->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede crear tareas.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $task = Task::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'project_id' => $validated['project_id'],
                'status_id' => TaskStatus::firstWhere('name', 'En espera de asignación')->id,
                'created_by' => auth()->id(),
            ]);

            $task->assignedUsers()->sync($validated['assigned_users']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea creada exitosamente.',
                'data' => $task->load(['project', 'status', 'assignedUsers', 'createdBy'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la tarea.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show($id)
    {
        $task = Task::with(['project', 'status', 'assignedUsers', 'createdBy'])->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tarea no encontrada.'
            ], 404);
        }

        $user = auth()->user();

        if (strtolower($user->role->name) === 'planeación') {
            return response()->json([
                'success' => true,
                'data' => $task
        ]);
    }
        $isAssignedToTask = $task->assignedUsers->contains($user->id);
        $isAssignedToProject = $task->project->assignedUsers->contains($user->id);

        if ($isAssignedToTask || $isAssignedToProject) {
            return response()->json([
                'success' => true,
                'data' => $task
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No tienes permiso para ver esta tarea.'
        ], 403);
    }
        public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'assigned_users' => 'sometimes|array',
            'assigned_users.*' => 'exists:users,id',
            'status_id' => 'sometimes|exists:task_statuses,id'        ]);

        $task = Task::findOrFail($id);
        $user = auth()->user();
        if (strtolower($user->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede actualizar tareas.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $task->update($request->only(['title', 'description', 'status_id']));

            if ($request->has('assigned_users')) {
                $task->assignedUsers()->sync($request->assigned_users);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea actualizada exitosamente.',
                'data' => $task->load(['status', 'assignedUsers'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la tarea.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'required|exists:task_statuses,id'
        ]);

        $task = Task::findOrFail($id);
        $user = auth()->user();

        if ($task->assignedUsers->contains($user->id) === false &&
            strtolower($user->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para cambiar el estado.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $task->update(['status_id' => $request->status_id]);
            $task->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estado de la tarea actualizado exitosamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado de la tarea.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tarea no encontrada.'
            ], 404);
        }

        if (strtolower(auth()->user()->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede eliminar tareas.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $task->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea eliminada exitosamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la tarea.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignedTasks()
    {
        $user = auth()->user();

        $tasks = Task::where('assigned_to', $user->id)
            ->orWhereHas('project', function($q) use ($user) {
                $q->whereHas('developers', function($q2) use ($user) {
                    $q2->where('user_id', $user->id);
                });
            })
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    public function assignUsers(Request $request, $id)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $task = Task::findOrFail($id);
        $user = auth()->user();

        if (strtolower($user->role->name) !== 'planeación') {
            return response()->json([
                'success' => false,
                'message' => 'Solo Planeación puede asignar usuarios a tareas.'
            ], 403);
        }

        // Verificar que los usuarios sean desarrolladores o testers
        $validUsers = User::whereIn('id', $request->user_ids)
            ->whereHas('role', function($q) {
                $q->whereIn('name', ['Desarrollador', 'Tester']);
            })->pluck('id');

        if ($validUsers->count() !== count($request->user_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden asignar Desarrolladores o Testers a tareas.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $task->assignedUsers()->sync($request->user_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuarios asignados exitosamente a la tarea.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar usuarios a la tarea.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getTaskStatuses()
    {
        try {
            $statuses = TaskStatus::select('id', 'name')
                ->orderBy('id')
                ->get()
                ->map(function($status) {
                    return [
                        'id' => $status->id,
                        'name' => $status->name,
                        'is_default' => $status->id == TaskStatus::PENDING_ASSIGNMENT
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $statuses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los estados de tareas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getTasksByStatus($status)
    {
        $user = auth()->user();
        $validStatuses = ['En proceso', 'Finalizada', 'En pruebas', 'Bug', 'En espera de asignación'];

        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de tarea no válido.'
            ], 400);
        }

        $query = Task::where('status_id', $status);

        if (strtolower($user->role->name) === 'planeación') {
            $tasks = $query->with('project')->paginate(10);
        } else {
            $tasks = $query->where('assigned_to', $user->id)
                ->orWhereHas('project', function($q) use ($user) {
                    $q->whereHas('developers', function($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
                })
                ->paginate(10);
        }

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }
}
