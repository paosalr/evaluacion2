<?php

namespace App\Http\Controllers;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::whereHas('developers', function ($query) {
            $query->where('user_id', auth()->user()->id);
        })->orWhere('status', 'Planeación')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:projects',
            'description' => 'required|string'
        ]);

        try{
            DB::beginTransaction();

            $project = Project::create([
                'name' => $request->name,
                'description' => $request->description,
                'creation_date' => now(),
                'status' => 'Planeación'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proyecto creado existosamente.',
                'data' => [
                    "project" => $project
                ]
            ]);
        }catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $project = Project::find($id);

        if(!$project) {
            return response()->json([
                'success' => 'false',
                'message' => 'Proyecto no encontrado.'
            ], 404);
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

        if(!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $project->fill($request->all());
            $project->save();

            DB::commit();

            return response()->json([
                'success' => false,
                'message' => 'Proyecto actualizado exitosamente.',
                'data' => [
                    "project" => $project
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
    public function updateStatus(Request $request, $id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        try{
            DB::beginTransaction();

            $project->status = $request->status;
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
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $project->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proyecto eliminado exitosamente.'
            ]);
    } catch (\Exception $e) {
        DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignDevelopers(Request $request, $id)
    {
    $project = Project::find($id);

    if (!$project) {
        return response()->json([
            'success' => false,
            'message' => 'Proyecto no encontrado.'
            ], 404);
    }

    if(auth()->user()->role->name !== 'Planeacion') {
        return response()->json([
            'success' => false,
            'message' => 'Solo los usuarios de Planeación pueden designar desarrolladores a proyectos.'
        ], 403);
    }

    $request->validate([
        'developer_ids' => 'required|array',
        'developer_ids.*' => 'exists:users,id'
    ]);

    try {
        DB::beginTransaction();

        $developers = User::whereIn('id', $request->developer_ids)
            ->where('role_id', Role::where('name', 'Desarrollador')->first()->id)
            ->get();

        if ($developers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron desarrolladores válidos.'
            ], 400);
        }

        $project->developers()->sync($developers->pluck('id'));

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Desarrolladores asignados exitosamente al proyecto.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Ha ocurrido un error: ' . $e->getMessage()
        ], 500);
        }
    }

    public function getTasksByProject($id)
    {
        try {
            $project = Project::with('tasks')->find($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proyecto no encontrado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $project->tasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
            ], 500);
        }
    }
}
