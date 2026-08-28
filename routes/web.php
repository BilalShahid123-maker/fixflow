<?php

use App\Http\Controllers\MaintenancePortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MaintenancePortalController::class, 'landing'])->name('home');

Route::get('/submit', [MaintenancePortalController::class, 'create'])->name('portal.create');
Route::post('/submit', [MaintenancePortalController::class, 'store'])->name('portal.store');

Route::get('/status/{reference}', [MaintenancePortalController::class, 'status'])
    ->where('reference', '^[A-Z0-9]{2,12}$')
    ->name('portal.status');
