<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        if (auth()->user()->role->name !== 'RH') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo usuarios RH pueden ver la lista de usuarios.'
            ], 403);
        }

        $users = User::paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
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
                'data' => [
                    "user" => $user
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = User::find($id);

        if(!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
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

        if($request->has('email') && $user->role->name !== 'RH') {
            return response()->json([
                'success' => false,
                'message' => 'Solo los usuarios RH pueden actualizar el correo.'
            ], 403);
        }
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'last_name_p' => 'sometimes|string|max:255',
            'last_name_m' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'role_id' => 'sometimes|exists:roles,id'
            ]);

        $user = User::find($id);

        if(!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $user = fill($request->all());
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente.',
                'data' => [
                    "user" => $user
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

    public function activeInactiveUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        if (auth()->user()->role->name !== 'RH') {
            return response()->json([
                'success' => false,
                'message' => 'Solo los usuarios RH pueden deshabilitar/activar usuarios.'
            ], 403);
        }

        try{
            DB::beginTransaction();

            $user->is_active = !$user->is_active;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario ' . ($user->is_active ? 'activado' : 'desactivado') . 'exitosamente.'
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
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

        if(auth()->user()->role->name !== 'RH'){
            return response()->json([
                'success' => false,
                'message' => 'Solo los usuarios RH pueden actualizar la contraseña.'
            ], 403);
        }
        $request->validate([
            'password' => 'required|confirmed|min:8',
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.'
        ]);

        try{
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
                'message' => 'Ha ocurrido un error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        if (auth()->user()->role->name !== 'RH') {
            return response()->json([
                'success' => false,
                'message' => 'Solo los usuarios RH pueden eliminar usuarios.'
            ], 403);
        }

        try{
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
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
