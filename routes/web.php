<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:Administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::resource('units', \App\Http\Controllers\UnitController::class);
    Route::resource('kelompok-belanja', \App\Http\Controllers\KelompokBelanjaController::class);
    Route::resource('account-codes', \App\Http\Controllers\AccountCodeController::class);
    Route::resource('periods', \App\Http\Controllers\RbaPeriodController::class);
    Route::resource('headers', \App\Http\Controllers\RbaHeaderController::class);
    Route::post('headers/{header}/toggle-status', [\App\Http\Controllers\RbaHeaderController::class, 'toggleStatus'])->name('headers.toggle-status');
    Route::get('headers/{header}/pagu', [\App\Http\Controllers\Admin\RbaAccountPaguController::class, 'index'])->name('headers.pagu.index');
    Route::post('headers/{header}/pagu', [\App\Http\Controllers\Admin\RbaAccountPaguController::class, 'store'])->name('headers.pagu.store');
});

Route::middleware(['auth', 'role:Supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::get('/dashboard', function () {
        return view('supervisor.dashboard');
    })->name('dashboard');

    Route::resource('users', \App\Http\Controllers\Supervisor\UserController::class);
    Route::get('submissions', [\App\Http\Controllers\Supervisor\ReviewController::class, 'index'])->name('submissions.index');
    Route::get('submissions/{submission}', [\App\Http\Controllers\Supervisor\ReviewController::class, 'show'])->name('submissions.show');
    Route::post('submissions/{submission}/validate', [\App\Http\Controllers\Supervisor\ReviewController::class, 'validate'])->name('submissions.validate');
    Route::post('details/{detail}/toggle-validation', [\App\Http\Controllers\Supervisor\ReviewController::class, 'toggleDetailValidation'])->name('details.toggle-validation');
    Route::post('details/{detail}/reject', [\App\Http\Controllers\Supervisor\ReviewController::class, 'rejectDetail'])->name('details.reject');
});

Route::get('history/{detail}', [\App\Http\Controllers\General\HistoryController::class, 'show'])
    ->middleware(['auth'])
    ->name('history.show');

Route::get('submissions/{submission}/documents/{type}/history', [\App\Http\Controllers\Operator\DocumentController::class, 'history'])
    ->middleware(['auth'])
    ->name('submissions.documents.history');

Route::middleware(['auth', 'role:Operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/dashboard', function () {
        return view('operator.dashboard');
    })->name('dashboard');

    Route::resource('submissions', \App\Http\Controllers\Operator\SubmissionController::class);
    Route::post('submissions/{submission}/submit', [\App\Http\Controllers\Operator\SubmissionController::class, 'submit'])->name('submissions.submit');
    Route::put('submissions/{submission}/background', [\App\Http\Controllers\Operator\SubmissionController::class, 'updateBackground'])->name('submissions.update-background');
    Route::post('submissions/{submission}/documents/upload', [\App\Http\Controllers\Operator\DocumentController::class, 'uploadDocument'])->name('submissions.documents.upload');
    Route::resource('details', \App\Http\Controllers\Operator\DetailController::class);
    Route::post('details/{detail}/submit-item', [\App\Http\Controllers\Operator\DetailController::class, 'submitItem'])->name('details.submit-item');
    Route::post('details/{detail}/upload-version', [\App\Http\Controllers\Operator\DetailController::class, 'uploadVersion'])->name('details.upload-version');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
