<?php

use App\Livewire\PasswordResetPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function (string $token) {
    $email = request()->query('email');

    $url = "/app/password-reset/{$token}";
    if ($email) {
        $url .= '?email=' . urlencode($email);
    }

    return redirect($url);
})->name('password.reset');

Route::middleware(['auth'])->group(function () {

    Route::get('/app/reset-password', PasswordResetPage::class)->name('password-reset-page');

    Route::get('/app/priv-storage/{filepath}', function ($filepath) {
        return Storage::disk('private')->download($filepath);
    })->where('filepath', '.*')->name('priv-storage');

});

// Add Socialite routes
Route::get('auth/{provider}/redirect', '\App\Http\Controllers\Auth\AuthController@redirectToProvider')->name('socialite.redirect');
Route::get('auth/{provider}/callback', '\App\Http\Controllers\Auth\AuthController@handleProviderCallback')->name('socialite.callback');
