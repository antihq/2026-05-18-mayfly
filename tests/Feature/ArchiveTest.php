<?php

use App\Models\Entry;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
});

test('archive page renders for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('archived-entries'))
        ->assertOk();
});

test('archive shows empty table when no expired entries', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::archive.show')
        ->assertSee('Content')
        ->assertSee('Type')
        ->assertSee('Expired');
});

test('archive shows expired entries', function () {
    $this->actingAs($this->user);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'I have expired',
    ]);

    Livewire::test('pages::archive.show')
        ->assertSee('I have expired');
});

test('archive does not show active entries', function () {
    $this->actingAs($this->user);
    Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'I am still active',
    ]);

    Livewire::test('pages::archive.show')
        ->assertDontSee('I am still active');
});

test('archive does not show entries from other teams', function () {
    $otherUser = User::factory()->create();
    Entry::factory()->expired()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
        'content' => 'Other team expired',
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::archive.show')
        ->assertDontSee('Other team expired');
});

test('expired entries can be restored', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    $before = now()->addHours(72);

    Livewire::test('pages::archive.show')
        ->call('restore', $entry->id);

    $entry = $entry->fresh();
    $after = now()->addHours(72);

    expect($entry->expires_at->diffInHours(now(), absolute: true))->toBeGreaterThanOrEqual(71)
        ->and($entry->expires_at->diffInHours(now(), absolute: true))->toBeLessThanOrEqual(73);
});

test('restoring an entry removes it from the archive', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Restore me',
    ]);

    Livewire::test('pages::archive.show')
        ->call('restore', $entry->id)
        ->assertDontSee('Restore me');
});

test('expired entries can be permanently deleted', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::archive.show')
        ->call('destroy', $entry->id);

    $this->assertDatabaseMissing('entries', ['id' => $entry->id]);
});

test('archive can be searched by content', function () {
    $this->actingAs($this->user);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Find this entry',
    ]);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Different entry',
    ]);

    $component = Livewire::test('pages::archive.show')
        ->set('search', 'Find this');

    $allEntries = $component->instance()->allEntries;
    $allContents = $allEntries->pluck('content')->toArray();

    expect($allContents)->toContain('Find this entry')
        ->and($allContents)->not->toContain('Different entry');
});

test('archive can be filtered by type', function () {
    $this->actingAs($this->user);
    Entry::factory()->expired()->task()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Expired task',
    ]);
    Entry::factory()->expired()->note()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Expired note',
    ]);

    $component = Livewire::test('pages::archive.show')
        ->set('filterType', 'task');

    $allEntries = $component->instance()->allEntries;
    $allContents = $allEntries->pluck('content')->toArray();

    expect($allContents)->toContain('Expired task')
        ->and($allContents)->not->toContain('Expired note');
});

test('cannot restore an entry from another team', function () {
    $otherUser = User::factory()->create();
    $entry = Entry::factory()->expired()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::archive.show')
        ->call('restore', $entry->id);
})->throws(ModelNotFoundException::class);

test('cannot destroy an entry from another team', function () {
    $otherUser = User::factory()->create();
    $entry = Entry::factory()->expired()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::archive.show')
        ->call('destroy', $entry->id);
})->throws(ModelNotFoundException::class);

test('recent groups returns at most 2 date groups', function () {
    $this->actingAs($this->user);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Day 1',
        'expires_at' => now()->subDay(),
    ]);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Day 2',
        'expires_at' => now()->subDays(2),
    ]);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Day 3',
        'expires_at' => now()->subDays(3),
    ]);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Day 4',
        'expires_at' => now()->subDays(4),
    ]);

    $component = Livewire::test('pages::archive.show');
    $groups = $component->instance()->recentGroups;

    expect($groups)->toHaveCount(2);
});

test('recent groups groups entries by expiry date', function () {
    $this->actingAs($this->user);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Entry A',
        'expires_at' => now()->subDay(),
    ]);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Entry B',
        'expires_at' => now()->subDay()->subHour(),
    ]);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Entry C',
        'expires_at' => now()->subDays(3),
    ]);

    $component = Livewire::test('pages::archive.show');
    $groups = $component->instance()->recentGroups;

    expect($groups)->toHaveCount(2);
    $firstGroup = $groups->first();
    expect($firstGroup)->toHaveCount(2);
});

test('formatDateGroup returns expired today for today', function () {
    $this->actingAs($this->user);

    $component = Livewire::test('pages::archive.show');
    $result = $component->instance()->formatDateGroup(now()->format('Y-m-d'));

    expect($result)->toEqual('Expired today');
});

test('formatDateGroup returns expired yesterday for yesterday', function () {
    $this->actingAs($this->user);

    $component = Livewire::test('pages::archive.show');
    $result = $component->instance()->formatDateGroup(now()->subDay()->format('Y-m-d'));

    expect($result)->toEqual('Expired yesterday');
});

test('formatDateGroup returns formatted date for older dates', function () {
    $this->actingAs($this->user);

    $component = Livewire::test('pages::archive.show');
    $date = now()->subDays(10)->format('Y-m-d');
    $result = $component->instance()->formatDateGroup($date);

    expect($result)->toStartWith('Expired ');
    expect($result)->not->toEqual('Expired today')
        ->and($result)->not->toEqual('Expired yesterday');
});

test('all entries paginates with 15 per page', function () {
    $this->actingAs($this->user);
    for ($i = 0; $i < 16; $i++) {
        Entry::factory()->expired()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'expires_at' => now()->subHours($i + 1),
        ]);
    }

    $component = Livewire::test('pages::archive.show');
    $allEntries = $component->instance()->allEntries;

    expect($allEntries)->toHaveCount(15)
        ->and($allEntries->hasMorePages())->toBeTrue();
});
