<?php

use App\Enums\BulletStatus;
use App\Enums\BulletType;
use App\Models\Bullet;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
});

test('bullets index page renders for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('bullets.index'))
        ->assertOk();
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('bullets index shows empty list when no bullets exist', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->assertSee('Log task');
});

test('bullets index shows active bullets in list', function () {
    $this->actingAs($this->user);
    Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'I am active',
    ]);

    Livewire::test('pages::bullets.index')
        ->assertSee('I am active');
});

test('bullets index does not show expired bullets in main list', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'I am expired',
    ]);

    $component = Livewire::test('pages::bullets.index');

    $bulletIds = $component->get('bullets')->pluck('id')->toArray();

    expect($bulletIds)->not->toContain($bullet->id);
});

test('bullets index does not show bullets from other teams', function () {
    $otherUser = User::factory()->create();
    Bullet::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
        'body' => 'Other team bullet',
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->assertDontSee('Other team bullet');
});

test('create page renders for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('bullets.create'))
        ->assertOk();
});

test('bullets can be created as tasks', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.create')
        ->set('body', 'Buy groceries')
        ->call('create', 'task')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('bullets', [
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => BulletType::Task->value,
        'body' => 'Buy groceries',
        'status' => BulletStatus::Active,
    ]);
});

test('bullets can be created as notes', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.create')
        ->set('body', 'Cool idea for later')
        ->call('create', 'note')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('bullets', [
        'team_id' => $this->team->id,
        'type' => BulletType::Note->value,
        'body' => 'Cool idea for later',
    ]);
});

test('creating a bullet sets expires_at to 72 hours from now', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.create')
        ->set('body', 'Test bullet')
        ->call('create', 'task');

    $bullet = Bullet::first();

    expect($bullet->expires_at->diffInHours(now(), absolute: true))->toBeGreaterThanOrEqual(71)
        ->and($bullet->expires_at->diffInHours(now(), absolute: true))->toBeLessThanOrEqual(73);
});

test('body is required when creating', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.create')
        ->set('body', '')
        ->call('create', 'task')
        ->assertHasErrors(['body' => 'required']);
});

test('body has a max length of 1000 characters when creating', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.create')
        ->set('body', str_repeat('a', 1001))
        ->call('create', 'task')
        ->assertHasErrors(['body' => 'max']);
});

test('show page renders for team bullet', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'View me',
    ]);

    $this->get(route('bullets.show', $bullet))
        ->assertOk()
        ->assertSee('View me');
});

test('show page aborts 403 for bullet from another team', function () {
    $otherUser = User::factory()->create();
    $bullet = Bullet::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('bullets.show', $bullet))
        ->assertForbidden();
});

test('bullet can be toggled complete from show page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.show', ['bullet' => $bullet->id])
        ->call('toggleComplete');

    expect($bullet->fresh()->status)->toEqual(BulletStatus::Completed);
});

test('edit page renders for team bullet', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'Edit me',
    ]);

    $this->get(route('bullets.edit', $bullet))
        ->assertOk()
        ->assertSee('Edit me');
});

test('edit page aborts 403 for bullet from another team', function () {
    $otherUser = User::factory()->create();
    $bullet = Bullet::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('bullets.edit', $bullet))
        ->assertForbidden();
});

test('bullets can be updated', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'Original body',
        'type' => BulletType::Task,
    ]);

    Livewire::test('pages::bullets.edit', ['bullet' => $bullet->id])
        ->set('body', 'Updated body')
        ->set('type', 'note')
        ->call('update')
        ->assertHasNoErrors();

    expect($bullet->fresh()->body)->toEqual('Updated body')
        ->and($bullet->fresh()->type)->toEqual(BulletType::Note);
});

test('editing body is required', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.edit', ['bullet' => $bullet->id])
        ->set('body', '')
        ->call('update')
        ->assertHasErrors(['body' => 'required']);
});

test('bullets can be deleted from edit page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.edit', ['bullet' => $bullet->id])
        ->call('delete');

    $this->assertDatabaseMissing('bullets', ['id' => $bullet->id]);
});

test('bullets index orders by expires_at descending', function () {
    $this->actingAs($this->user);
    $first = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'Most time left',
        'expires_at' => now()->addHours(72),
    ]);
    $second = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'Least time left',
        'expires_at' => now()->addHours(10),
    ]);

    $component = Livewire::test('pages::bullets.index');
    $bulletIds = $component->get('bullets')->pluck('id')->toArray();

    expect($bulletIds)->toEqual([$first->id, $second->id]);
});

test('create redirects to bullets index', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.create')
        ->set('body', 'Test bullet')
        ->call('create', 'task')
        ->assertRedirect(route('bullets.index'));
});

test('show page toggle complete redirects to bullets index', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.show', ['bullet' => $bullet->id])
        ->call('toggleComplete')
        ->assertRedirect(route('bullets.index'));
});

test('show page does not show complete button for notes', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->note()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    $this->get(route('bullets.show', $bullet))
        ->assertDontSee('Complete')
        ->assertDontSee('Reopen');
});

test('edit redirects to bullets show after update', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.edit', ['bullet' => $bullet->id])
        ->set('body', 'Updated')
        ->call('update')
        ->assertRedirect(route('bullets.index'));
});

test('edit redirects to bullets index after delete', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.edit', ['bullet' => $bullet->id])
        ->call('delete')
        ->assertRedirect(route('bullets.index'));
});

test('editing body has a max length of 1000 characters', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.edit', ['bullet' => $bullet->id])
        ->set('body', str_repeat('a', 1001))
        ->call('update')
        ->assertHasErrors(['body' => 'max']);
});

test('task can be completed from index page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => BulletType::Task,
    ]);

    Livewire::test('pages::bullets.index')
        ->call('complete', $bullet->id);

    expect($bullet->fresh()->status)->toEqual(BulletStatus::Completed);
});

test('task can be cancelled from index page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => BulletType::Task,
    ]);

    Livewire::test('pages::bullets.index')
        ->call('cancel', $bullet->id);

    expect($bullet->fresh()->status)->toEqual(BulletStatus::Cancelled);
});

test('bullet can be migrated from index page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.index')
        ->call('migrate', $bullet->id);

    expect($bullet->fresh()->status)->toEqual(BulletStatus::Migrated);
});

test('inline bullet can be created as task from index page', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->set('newBody', 'Buy groceries')
        ->call('create', 'task')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('bullets', [
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => BulletType::Task->value,
        'body' => 'Buy groceries',
        'status' => BulletStatus::Active,
    ]);
});

test('inline bullet can be created as note from index page', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->set('newBody', 'Cool idea')
        ->call('create', 'note')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('bullets', [
        'team_id' => $this->team->id,
        'type' => BulletType::Note->value,
        'body' => 'Cool idea',
    ]);
});

test('inline bullet body is required on index page', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->set('newBody', '')
        ->call('create', 'task')
        ->assertHasErrors(['newBody' => 'required']);
});

test('inline bullet body has max length of 1000 characters on index page', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->set('newBody', str_repeat('a', 1001))
        ->call('create', 'task')
        ->assertHasErrors(['newBody' => 'max']);
});

test('completing bullet from another team throws not found on index page', function () {
    $otherUser = User::factory()->create();
    $bullet = Bullet::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->call('complete', $bullet->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('cancelling bullet from another team throws not found on index page', function () {
    $otherUser = User::factory()->create();
    $bullet = Bullet::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->call('cancel', $bullet->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('migrating bullet from another team throws not found on index page', function () {
    $otherUser = User::factory()->create();
    $bullet = Bullet::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::bullets.index')
        ->call('migrate', $bullet->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('completed task can be reopened from show page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->completed()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.show', ['bullet' => $bullet->id])
        ->call('toggleComplete');

    expect($bullet->fresh()->status)->toEqual(BulletStatus::Active);
});
