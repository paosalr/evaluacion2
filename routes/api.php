<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('v1')->group(function (){
    Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('checkRole:RH')->group(function(){
        Route::prefix('users')->group(function () {
            Route::get('index', [UserController::class, 'index'])->name('users.index');
            Route::post('store', [UserController::class, 'store'])->name('users.store');
            Route::get('show/{id}', [UserController::class, 'show'])->name('users.show');
            Route::put('update/{id}', [UserController::class, 'update'])->name('users.update');
            Route::put('update-password/{id}', [UserController::class, 'updatePassword'])->name('users.update.password');
            Route::put('active-inactive/{id}', [UserController::class, 'activeInactiveUser'])->name('users.active-inactive');
            Route::delete('delete/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        });
    });

    /***** PLANEACIÓN *****/
    Route::middleware('checkRole:Planeacion')->group(function () {

    /***** PLANEACIÓN *****/
    Route::prefix('projects')->group(function () {
        Route::get('index', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('store', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('show/{id}', [ProjectController::class, 'show'])->name('projects.show');
        Route::put('update/{id}', [ProjectController::class, 'update'])->name('projects.update');
        Route::put('update-status/{id}', [ProjectController::class, 'updateStatus'])->name('projects.update.status');
        Route::post('assign-developers/{id}', [ProjectController::class, 'assignDevelopers'])->name('projects.assign.developers');
        Route::delete('delete/{id}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    });

    /***** TAREAS *****/
    Route::prefix('tasks')->group(function () {
        Route::get('index', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('store', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('show/{id}', [TaskController::class, 'show'])->name('tasks.show');
        Route::put('update/{id}', [TaskController::class, 'update'])->name('tasks.update');
        Route::put('update-status/{id}', [TaskController::class, 'updateStatus'])->name('tasks.update.status');
        Route::delete('delete/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    });
    });

    /***** DESARROLLADORES Y TESTERS *****/
    Route::middleware('checkRole:Desarrollador,Tester')->group(function () {

    /***** TAREAS ASIGNADAS *****/
    Route::prefix('tasks')->group(function () {
        Route::get('assigned', [TaskController::class, 'assignedTasks'])->name('tasks.assigned');
        Route::put('update-status/{id}', [TaskController::class, 'updateStatus'])->name('tasks.update.status');
        });
        });
    });
});

