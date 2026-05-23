<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages::home')->name('home')->middleware('guest');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('/dashboard', 'pages.dashboard')->name('dashboard');
        Route::livewire('/bullets', 'pages::bullets.index')->name('bullets.index');
        Route::livewire('/bullets/create', 'pages::bullets.create')->name('bullets.create');
        Route::livewire('/bullets/{bullet}', 'pages::bullets.show')->name('bullets.show');
        Route::livewire('/bullets/{bullet}/edit', 'pages::bullets.edit')->name('bullets.edit');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}', 'pages::invitations.show')->name('invitations.show');
});

require __DIR__.'/settings.php';
