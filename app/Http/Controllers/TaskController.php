<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::paginate(10);

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|exists:projects,id'
        ]);

        try{
            DB::beginTransaction();

            $task = Task::create([
                'title' => $request->title,
                'description' => $request->description,
                'creation_date' => now(),
                'status' => 'En espera de asignación',
                'project_id' => $request->project_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea creada exitosamente.',
                'data' => [
                    "task" => $task
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tarea no encontrada.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
        }

        public function update(Request $request, $id)
        {
            $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:En proceso,Finalizada,En pruebas,Bug,En espera de asignación'
            ]);

            $task = Task::find($id);

            if(!$task){
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada.'
                ], 404);
            }

            try{
                DB::beginTransaction();

                $task->fill($request->all());
                $task->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Tarea actualizada exitosamente.',
                    'data' => [
                        "task" => $task
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Ha ocurrido un error: ' . $e->getMessage()
                ], 500);
            }
        }

        public function destroy($id)
        {
            $task = Task::destroy($id);

            if(!$task){
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada.'
                ], 404);
            }

            try{
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
                    'message' => 'Ha ocurrido un error: ' . $e->getMessage()
                ], 500);
            }
        }

    public function assignedTasks(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.'
            ], 401);
        }

        try {
            DB::beginTransaction();

            $tasks = Task::where('assigned_to', $user->id)
            ->orWhere('status', 'En proceso')
            ->paginate(10);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
            ], 500);
        }
    }

}
