<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        if (strtolower(auth()->user()->role->name) !== 'rh') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo RH puede ver la lista de usuarios.'
            ], 403);
        }

        $users = User::with('role')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        if (strtolower(auth()->user()->role->name) !== 'rh') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo RH puede crear usuarios.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'last_name_p' => 'required|string|max:255',
            'last_name_m' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'last_name_p' => $request->last_name_p,
                'last_name_m' => $request->last_name_m,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'registration_date' => now(),
                'role_id' => $request->role_id,
                'is_active' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente.',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        $authUser = auth()->user();
        if (strtolower($authUser->role->name) !== 'rh' && $authUser->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes ver tu propio perfil.'
            ], 403);
        }
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        $authUser = auth()->user();
        $isRH = strtolower($authUser->role->name) === 'rh';

        if (!$isRH && $authUser->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes actualizar tu propio perfil.'
            ], 403);
        }

        $validationRules = [
            'name' => 'sometimes|string|max:255',
            'last_name_p' => 'sometimes|string|max:255',
            'last_name_m' => 'sometimes|string|max:255'
        ];

        if ($isRH) {
            $validationRules['email'] = 'sometimes|string|email|max:255|unique:users,email,' . $id;
            $validationRules['role_id'] = 'sometimes|exists:roles,id';
        }

        $request->validate($validationRules);

        try {
            DB::beginTransaction();

            $updateData = $request->only(['name', 'last_name_p', 'last_name_m']);

            if ($isRH) {
                if ($request->has('email')) {
                    $updateData['email'] = $request->email;
                }
                if ($request->has('role_id')) {
                    $updateData['role_id'] = $request->role_id;
                }
            }

            $user->update($updateData);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente.',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePassword(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        $authUser = auth()->user();
        $isRH = strtolower($authUser->role->name) === 'rh';

        if (!$isRH && $authUser->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes actualizar tu propia contraseña.'
            ], 403);
        }

        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        try {
            DB::beginTransaction();

            $user->password = Hash::make($request->password);
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la contraseña.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activeInactiveUser($id)
    {
        if (strtolower(auth()->user()->role->name) !== 'rh') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo RH puede activar/desactivar usuarios.'
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $user->is_active = !$user->is_active;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario ' . ($user->is_active ? 'activado' : 'desactivado') . ' exitosamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (strtolower(auth()->user()->role->name) !== 'rh') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo RH puede eliminar usuarios.'
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTestersDevelopers()
    {
        if (strtolower(auth()->user()->role->name) !== 'rh') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo RH puede ver esta lista.'
            ], 403);
        }

        try {
            $roles = Role::whereIn('name', ['Desarrollador', 'Tester'])
                ->pluck('id')
                ->toArray();

            $users = User::whereIn('role_id', $roles)
                ->with('role')
                ->select('id', 'name', 'last_name_p', 'last_name_m', 'email', 'role_id', 'is_active')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de desarrolladores y testers.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateCurrentPassword(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8'
            ]);

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta.'
                ], 401);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la contraseña.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene usuarios por rol (optimizada)
     */
    public function getUsersByRole($role)
    {
        if (strtolower(auth()->user()->role->name) !== 'rh') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo RH puede ver esta lista.'
            ], 403);
        }

        try {
            $validRoles = ['desarrollador', 'tester', 'planeación', 'rh'];

            if (!in_array(strtolower($role), $validRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no válido.'
                ], 400);
            }

            $roleId = Role::where('name', 'like', $role)->first()->id;

            $users = User::where('role_id', $roleId)
                ->with('role')
                ->select('id', 'name', 'last_name_p', 'last_name_m', 'email', 'role_id', 'is_active')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de usuarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableRoles()
    {
        if (strtolower(auth()->user()->role->name) !== 'rh') {
            return response()->json([
                'success' => false,
                'message' => 'Solo RH puede ver los roles disponibles'
            ], 403);
        }

        try {
            $roles = Role::select('id', 'name')->get();

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // En UserController.php
    public function toggleStatus($id)
    {
        if (strtolower(auth()->user()->role->name) !== 'rh') {
            return response()->json([
                'success' => false,
                'message' => 'Solo RH puede activar/desactivar usuarios.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($id);
            $user->is_active = !$user->is_active;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario ' . ($user->is_active ? 'activado' : 'desactivado') . ' exitosamente.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
