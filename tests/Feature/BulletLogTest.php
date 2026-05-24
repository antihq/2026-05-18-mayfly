<?php

use App\Enums\BulletStatus;
use App\Enums\BulletType;
use App\Models\Bullet;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

test('dashboard redirects to bullets index', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('bullets.index'));
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

test('task can be completed from index page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'type' => BulletType::Task,
    ]);

    Livewire::test('pages::bullets.index')
        ->call('complete', $bullet->id);

    $bullet = $bullet->fresh();

    expect($bullet->status)->toEqual(BulletStatus::Completed)
        ->and($bullet->completed_at)->not->toBeNull();
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
})->throws(ModelNotFoundException::class);
