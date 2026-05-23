<?php

use App\Enums\EntryStatus;
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

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('entries index shows empty list when no entries exist', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->assertSee('Log task');
});

test('entries index shows active entries in list', function () {
    $this->actingAs($this->user);
    Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'I am active',
    ]);

    Livewire::test('pages::entries.index')
        ->assertSee('I am active');
});

test('entries index does not show expired entries in main list', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'content' => 'I am expired',
    ]);

    $component = Livewire::test('pages::entries.index');

    $entryIds = $component->get('entries')->pluck('id')->toArray();

    expect($entryIds)->not->toContain($entry->id);
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
        ->call('create', 'task')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => EntryType::Task->value,
        'content' => 'Buy groceries',
        'status' => EntryStatus::Active,
    ]);
});

test('entries can be created as notes', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', 'Cool idea for later')
        ->call('create', 'note')
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
        ->call('create', 'task');

    $entry = Entry::first();

    expect($entry->expires_at->diffInHours(now(), absolute: true))->toBeGreaterThanOrEqual(71)
        ->and($entry->expires_at->diffInHours(now(), absolute: true))->toBeLessThanOrEqual(73);
});

test('content is required when creating', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', '')
        ->call('create', 'task')
        ->assertHasErrors(['content' => 'required']);
});

test('content has a max length of 1000 characters when creating', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', str_repeat('a', 1001))
        ->call('create', 'task')
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

    expect($entry->fresh()->status)->toEqual(EntryStatus::Completed);
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

test('entries index orders by expires_at descending', function () {
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

test('create redirects to entries index', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.create')
        ->set('content', 'Test entry')
        ->call('create', 'task')
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
        ->assertRedirect(route('entries.index'));
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

test('task can be completed from index page', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => EntryType::Task,
    ]);

    Livewire::test('pages::entries.index')
        ->call('complete', $entry->id);

    expect($entry->fresh()->status)->toEqual(EntryStatus::Completed);
});

test('task can be cancelled from index page', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => EntryType::Task,
    ]);

    Livewire::test('pages::entries.index')
        ->call('cancel', $entry->id);

    expect($entry->fresh()->status)->toEqual(EntryStatus::Cancelled);
});

test('entry can be migrated from index page', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.index')
        ->call('migrate', $entry->id);

    expect($entry->fresh()->status)->toEqual(EntryStatus::Migrated);
});

test('inline entry can be created as task from index page', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->set('newContent', 'Buy groceries')
        ->call('create', 'task')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => EntryType::Task->value,
        'content' => 'Buy groceries',
        'status' => EntryStatus::Active,
    ]);
});

test('inline entry can be created as note from index page', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->set('newContent', 'Cool idea')
        ->call('create', 'note')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'team_id' => $this->team->id,
        'type' => EntryType::Note->value,
        'content' => 'Cool idea',
    ]);
});

test('inline entry content is required on index page', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->set('newContent', '')
        ->call('create', 'task')
        ->assertHasErrors(['newContent' => 'required']);
});

test('inline entry content has max length of 1000 characters on index page', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->set('newContent', str_repeat('a', 1001))
        ->call('create', 'task')
        ->assertHasErrors(['newContent' => 'max']);
});

test('completing entry from another team throws not found on index page', function () {
    $otherUser = User::factory()->create();
    $entry = Entry::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->call('complete', $entry->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('cancelling entry from another team throws not found on index page', function () {
    $otherUser = User::factory()->create();
    $entry = Entry::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->call('cancel', $entry->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('migrating entry from another team throws not found on index page', function () {
    $otherUser = User::factory()->create();
    $entry = Entry::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::entries.index')
        ->call('migrate', $entry->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('completed task can be reopened from show page', function () {
    $this->actingAs($this->user);
    $entry = Entry::factory()->completed()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::entries.show', ['entry' => $entry->id])
        ->call('toggleComplete');

    expect($entry->fresh()->status)->toEqual(EntryStatus::Active);
});
