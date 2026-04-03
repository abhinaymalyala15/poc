<?php

use App\Http\Controllers\Admin\CallLogController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/calls', [CallLogController::class, 'index'])->name('calls.index');
    Route::get('/calls/data', [CallLogController::class, 'data'])->name('calls.data');
});

Route::post('/incoming-call', [CallController::class, 'incomingCall'])
    ->middleware('twilio.signature')
    ->name('twilio.incoming-call');

Route::post('/process-recording', [CallController::class, 'processRecording'])
    ->middleware('twilio.signature')
    ->name('twilio.process-recording');

require __DIR__.'/auth.php';
