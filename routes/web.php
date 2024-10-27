<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\AdminUserController;

use App\Http\Middleware\EnsureUserIsAdmin;

Route::get('/page/{i}', [HomeController::class, 'show']);

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //adventure page
    Route::get('/characterSelect', [NodeController::class, 'characterSelect'])->name('view.characterSelect');
    //adventure api bits.
    Route::post('/character-select/confirm', [NodeController::class, 'confirmSelection'])->name('character.select.confirm');


    Route::get('/adventure', [NodeController::class, 'view'])->name('view.adventure');
    Route::post('/save-progress', [NodeController::class, 'saveProgress'])->name('view.saveProgress');
    Route::get('/load-progress', [NodeController::class, 'loadProgress'])->name('view.loadProgress');
    Route::middleware('isAdmin')->group(function () {
        Route::get('/StoryCreator', [NodeController::class, 'showStoryCreator'])->name('node.showStoryCreator');

        Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users/store', [AdminUserController::class, 'store'])->name('admin.users.store');

        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');

    });
});

Route::middleware(['api'])->group(function () {
    Route::get('/api/nodes', [NodeController::class, 'index'])->name('node.index');
    Route::post('/api/nodes/store', [NodeController::class, 'store'])->name('node.store');
});
require __DIR__.'/auth.php';