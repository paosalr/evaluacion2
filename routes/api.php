<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;

Route::prefix('v1')->group(function () {
    // Autenticación pública
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    // Rutas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

// 2. Perfil del usuario (actualización de datos personales)
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::put('/', [UserController::class, 'updateCurrent'])->name('update'); // Solo actualiza nombre y apellidos
        });

        // 3. Gestión de Usuarios (solo RH)
        Route::middleware('checkRole:rh')->prefix('users')->name('users.')->group(function () {
            // 3.1 Listar usuarios (nombre, correo y rol)
            Route::get('/', [UserController::class, 'index'])->name('index');

            // 3.2 Registrar nuevos usuarios
            Route::post('/', [UserController::class, 'store'])->name('store');

            // 3.3 Obtener roles disponibles
            Route::get('/roles', [UserController::class, 'getAvailableRoles'])->name('roles.index');

            Route::put('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle.status');


            // Operaciones por usuario específico
            Route::prefix('{user}')->group(function () {
                // 3.4 Ver detalles
                Route::get('/', [UserController::class, 'show'])->name('show');

                // 3.5 Actualizar (incluye correo - solo RH)
                Route::put('/', [UserController::class, 'update'])->name('update');

                // 3.6 Actualizar contraseña (solo RH)
                Route::put('/password', [UserController::class, 'updatePassword'])->name('password');

                // 3.7 Deshabilitar/habilitar usuario (solo RH)
                Route::put('/status', [UserController::class, 'toggleStatus'])->name('status');

                // 3.8 Eliminar usuario (solo RH)
                Route::delete('/', [UserController::class, 'destroy'])->name('destroy');
            });
        });


        // 4. Gestión de Proyectos
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('project-statuses', [ProjectController::class, 'getProjectStatuses'])->name('project_statuses');

            // 4.4 Operaciones solo para Planeación
            Route::middleware('checkRole:planeación')->group(function () {
                // 4.4.1 Crear proyectos
                Route::post('/', [ProjectController::class, 'store'])->name('store');

                // 4.4.2 Obtener desarrolladores asignables
                Route::get('/assignable-developers', [ProjectController::class, 'getAssignableDevelopers'])->name('assignable.devs');
            });

            // 4.1 Listar proyectos (todos los roles excepto RH)
            Route::get('/', [ProjectController::class, 'index'])->middleware('checkRole:planeación,desarrollador,tester');

            // 4.2 Ver detalles de proyecto (todos excepto RH)
            Route::get('/{project}', [ProjectController::class, 'show'])->middleware('checkRole:planeación,desarrollador,tester')->name('show');
            // 4.3 Ver tareas por proyecto (todos excepto RH)
            Route::get('/{project}/tasks', [ProjectController::class, 'getTasksByProject'])->middleware('checkRole:planeación,desarrollador,tester')->name('tasks');
            Route::get('projects/{project}/tasks', [ProjectController::class, 'getTasksByProject'])->name('projects.tasks');

            // 4.5 Operaciones exclusivas de Planeación
            Route::middleware('checkRole:planeación')->group(function () {
                // 4.5.1 Actualizar nombre y descripción
                Route::put('/{project}', [ProjectController::class, 'update'])->name('update');

                // 4.5.2 Cambiar estado
                Route::put('/{project}/status', [ProjectController::class, 'updateStatus'])->name('status');

                // 4.5.3 Asignar desarrolladores
                Route::post('/{project}/developers', [ProjectController::class, 'assignDevelopers'])->name('assign.developers');

                // 4.5.4 Eliminar proyecto
                Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
            });
        });

        // 5. Gestión de Tareas
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('task-statuses', [TaskController::class, 'getTaskStatuses'])->middleware('checkRole:planeación,desarrollador,tester');
            // 5.1.2 Cambiar estado de tarea
            Route::put('/{task}/status', [TaskController::class, 'updateStatus'])->name('update.status');
            // 5.1 Operaciones para Desarrolladores y Testers
            Route::middleware('checkRole:desarrollador,tester')->group(function () {
                // 5.1.1 Ver tareas asignadas
                Route::get('/assigned', [TaskController::class, 'assignedTasks'])->name('assigned');
            });

            Route::get('/', [TaskController::class, 'index'])->name('index');

            // 5.2 Operaciones exclusivas de Planeación
            Route::middleware('checkRole:planeación')->group(function () {
                // 5.2.1 Crear tareas
                Route::post('/', [TaskController::class, 'store'])->name('store');

                // 5.2.3 Ver detalles de tarea
                Route::get('/{task}', [TaskController::class, 'show'])->name('show');

                // 5.2.4 Actualizar tarea (título, descripción, asignados)
                Route::put('/{task}', [TaskController::class, 'update'])->name('update');

                // 5.2.5 Asignar usuarios a tarea
                Route::post('/{task}/assign', [TaskController::class, 'assignUsers'])->name('assign.users');

                // 5.2.6 Eliminar tarea
                Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
            });
        });
    });
});
