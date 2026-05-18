<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages::home')->name('home')->middleware('guest');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('/dashboard', fn () => redirect()->route('entries.index', ['current_team' => request()->route('current_team')]))->name('dashboard');
        Route::livewire('/entries', 'pages::entries.index')->name('entries.index');
        Route::livewire('/entries/create', 'pages::entries.create')->name('entries.create');
        Route::livewire('/entries/{entry}', 'pages::entries.show')->name('entries.show');
        Route::livewire('/entries/{entry}/edit', 'pages::entries.edit')->name('entries.edit');
        Route::livewire('/archived-entries', 'pages::archive.show')->name('archived-entries');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}', 'pages::invitations.show')->name('invitations.show');
});

require __DIR__.'/settings.php';
