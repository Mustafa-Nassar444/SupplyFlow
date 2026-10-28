<?php

use App\Http\Controllers\dashboard\CategoryController;
use App\Http\Controllers\DashboardExampleController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Keep one authenticated dashboard route, using the project's Tabler view.
Route::middleware(['auth', 'can:viewAny,App\Models\Language'])->prefix('dashboard/languages')->name('languages.')->group(function () {
    Route::get('/', [LanguageController::class, 'index'])->name('index');
    Route::patch('/{language}', [LanguageController::class, 'update'])->name('update');
});
Route::post('/dashboard/language', LocaleController::class)->name('dashboard.locale');
// Route::get('/dashboard/examples/form', DashboardExampleController::class)->name('dashboard.examples.form');

Route::resource('dashboard/categories', CategoryController::class)->middleware(['auth'])->names('dashboard.categories');
