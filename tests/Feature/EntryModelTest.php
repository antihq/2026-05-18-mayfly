<?php

use App\Enums\EntryType;
use App\Models\Entry;
use Carbon\CarbonInterface;

test('scopeActive returns entries that have not expired', function () {
    $active = Entry::factory()->create(['expires_at' => now()->addHours(48)]);
    $expired = Entry::factory()->create(['expires_at' => now()->subHour()]);

    $results = Entry::active()->pluck('id');

    expect($results)->toContain($active->id);
    expect($results)->not->toContain($expired->id);
});

test('scopeExpired returns entries past their expires_at', function () {
    $active = Entry::factory()->create(['expires_at' => now()->addHours(48)]);
    $expired = Entry::factory()->create(['expires_at' => now()->subHour()]);

    $results = Entry::expired()->pluck('id');

    expect($results)->toContain($expired->id);
    expect($results)->not->toContain($active->id);
});

test('isExpired returns true when expires_at is in the past', function () {
    $entry = Entry::factory()->make(['expires_at' => now()->subMinute()]);

    expect($entry->isExpired())->toBeTrue();
});

test('isExpired returns false when expires_at is in the future', function () {
    $entry = Entry::factory()->make(['expires_at' => now()->addHour()]);

    expect($entry->isExpired())->toBeFalse();
});

test('timeRemaining returns Expired for past entries', function () {
    $entry = Entry::factory()->make(['expires_at' => now()->subHour()]);

    expect($entry->timeRemaining())->toEqual('Expired');
});

test('timeRemaining shows hours when more than a day remains', function () {
    $entry = Entry::factory()->make(['expires_at' => now()->addHours(50)]);

    expect($entry->timeRemaining())->toContain('h left');
});

test('timeRemaining shows hours when less than a day remains', function () {
    $entry = Entry::factory()->make(['expires_at' => now()->addHours(5)->addMinutes(30)]);

    expect($entry->timeRemaining())->toEqual('5h left');
});

test('timeRemaining shows zero hours when less than an hour remains', function () {
    $entry = Entry::factory()->make(['expires_at' => now()->addMinutes(30)]);

    expect($entry->timeRemaining())->toEqual('0h left');
});

test('timeRemaining shows zero hours when less than a minute remains', function () {
    $entry = Entry::factory()->make(['expires_at' => now()->addSeconds(10)]);

    expect($entry->timeRemaining())->toEqual('0h left');
});

test('type is cast to EntryType enum', function () {
    $entry = Entry::factory()->create(['type' => EntryType::Task]);

    expect($entry->type)->toBeInstanceOf(EntryType::class);
    expect($entry->type)->toEqual(EntryType::Task);
});

test('expires_at is cast to datetime', function () {
    $entry = Entry::factory()->create();

    expect($entry->expires_at)->toBeInstanceOf(CarbonInterface::class);
});

test('is_completed defaults to false', function () {
    $entry = Entry::factory()->create();

    expect($entry->is_completed)->toBeFalse();
});

test('EntryType Task has correct icon and label', function () {
    expect(EntryType::Task->icon())->toEqual('check-circle')
        ->and(EntryType::Task->label())->toEqual('Task');
});

test('EntryType Note has correct icon and label', function () {
    expect(EntryType::Note->icon())->toEqual('light-bulb')
        ->and(EntryType::Note->label())->toEqual('Note');
});

test('entry belongs to a team', function () {
    $entry = Entry::factory()->create();

    expect($entry->team)->not->toBeNull()
        ->and($entry->team->id)->toEqual($entry->team_id);
});

test('entry belongs to a user', function () {
    $entry = Entry::factory()->create();

    expect($entry->user)->not->toBeNull()
        ->and($entry->user->id)->toEqual($entry->user_id);
});
