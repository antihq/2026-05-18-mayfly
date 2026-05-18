<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
});

test('entries index page renders for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('entries.index'))
        ->assertOk();
});

test('dashboard redirects to entries index', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('entries.index'));
});

test('entries index shows empty table when no entries exist', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->assertSee('Content')
        ->assertSee('Type')
        ->assertSee('Expires');
});

test('entries index shows active entries in table', function () {
    $this->actingAs($this->user);
    Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'I am active',
    ]);

    Livewire::test('pages::entries.index')
        ->assertSee('I am active');
});

test('entries index does not show expired entries', function () {
    $this->actingAs($this->user);
    Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'I am expired',
    ]);

    Livewire::test('pages::entries.index')
        ->assertDontSee('I am expired');
});

test('entries index does not show entries from other teams', function () {
    $otherUser = User::factory()->create();
    Entry::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
        'content' => 'Other team entry',
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->assertDontSee('Other team entry');
});

test('create page renders for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('entries.create'))
        ->assertOk();
});

test('entries can be created as tasks', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', 'Buy groceries')
        ->set('type', 'task')
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => EntryType::Task->value,
        'content' => 'Buy groceries',
        'is_completed' => false,
    ]);
});

test('entries can be created as notes', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', 'Cool idea for later')
        ->set('type', 'note')
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'team_id' => $this->team->id,
        'type' => EntryType::Note->value,
        'content' => 'Cool idea for later',
    ]);
});

test('creating an entry sets expires_at to 72 hours from now', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', 'Test entry')
        ->call('create');

    $entry = Entry::first();

    expect($entry->expires_at->diffInHours(now(), absolute: true))->toBeGreaterThanOrEqual(71)
        ->and($entry->expires_at->diffInHours(now(), absolute: true))->toBeLessThanOrEqual(73);
});

test('content is required when creating', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', '')
        ->call('create')
        ->assertHasErrors(['content' => 'required']);
});

test('content has a max length of 1000 characters when creating', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', str_repeat('a', 1001))
        ->call('create')
        ->assertHasErrors(['content' => 'max']);
});

test('show page renders for team entry', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'View me',
    ]);

    $this->get(route('entries.show', $entry))
        ->assertOk()
        ->assertSee('View me');
});

test('show page aborts 403 for entry from another team', function () {
    $otherUser = User::factory()->create();
    $entry = Entry::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('entries.show', $entry))
        ->assertForbidden();
});

test('entry can be toggled complete from show page', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.show', ['entry' => $entry->id])
        ->call('toggleComplete');

    expect($entry->fresh()->is_completed)->toBeTrue();
});

test('edit page renders for team entry', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Edit me',
    ]);

    $this->get(route('entries.edit', $entry))
        ->assertOk()
        ->assertSee('Edit me');
});

test('edit page aborts 403 for entry from another team', function () {
    $otherUser = User::factory()->create();
    $entry = Entry::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('entries.edit', $entry))
        ->assertForbidden();
});

test('entries can be updated', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Original content',
        'type' => EntryType::Task,
    ]);

    Livewire::test('pages::entries.edit', ['entry' => $entry->id])
        ->set('content', 'Updated content')
        ->set('type', 'note')
        ->call('update')
        ->assertHasNoErrors();

    expect($entry->fresh()->content)->toEqual('Updated content')
        ->and($entry->fresh()->type)->toEqual(EntryType::Note);
});

test('editing content is required', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.edit', ['entry' => $entry->id])
        ->set('content', '')
        ->call('update')
        ->assertHasErrors(['content' => 'required']);
});

test('entries can be deleted from edit page', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.edit', ['entry' => $entry->id])
        ->call('delete');

    $this->assertDatabaseMissing('entries', ['id' => $entry->id]);
});

test('entries index default sort is by expires_at descending', function () {
    $this->actingAs($this->user);
    $first = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Most time left',
        'expires_at' => now()->addHours(72),
    ]);
    $second = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'Least time left',
        'expires_at' => now()->addHours(10),
    ]);

    $component = Livewire::test('pages::entries.index');
    $entryIds = $component->get('entries')->pluck('id')->toArray();

    expect($entryIds)->toEqual([$first->id, $second->id]);
});

test('sortBy toggles direction when same field clicked', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->call('sortBy', 'content')
        ->assertSet('sortField', 'content')
        ->assertSet('sortDirection', 'asc')
        ->call('sortBy', 'content')
        ->assertSet('sortField', 'content')
        ->assertSet('sortDirection', 'desc');
});

test('sortBy resets to asc when new field clicked', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->call('sortBy', 'content')
        ->assertSet('sortField', 'content')
        ->assertSet('sortDirection', 'asc')
        ->call('sortBy', 'expires_at')
        ->assertSet('sortField', 'expires_at')
        ->assertSet('sortDirection', 'asc');
});

test('create redirects to entries index', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', 'Test entry')
        ->call('create')
        ->assertRedirect(route('entries.index'));
});

test('show page toggle complete redirects to entries index', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.show', ['entry' => $entry->id])
        ->call('toggleComplete')
        ->assertRedirect(route('entries.index'));
});

test('show page does not show complete button for notes', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->note()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    $this->get(route('entries.show', $entry))
        ->assertDontSee('Complete')
        ->assertDontSee('Reopen');
});

test('edit redirects to entries show after update', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.edit', ['entry' => $entry->id])
        ->set('content', 'Updated')
        ->call('update')
        ->assertRedirect(route('entries.show', $entry));
});

test('edit redirects to entries index after delete', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.edit', ['entry' => $entry->id])
        ->call('delete')
        ->assertRedirect(route('entries.index'));
});

test('editing content has a max length of 1000 characters', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.edit', ['entry' => $entry->id])
        ->set('content', str_repeat('a', 1001))
        ->call('update')
        ->assertHasErrors(['content' => 'max']);
});
