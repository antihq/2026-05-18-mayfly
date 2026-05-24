<?php

use App\Enums\BulletStatus;
use App\Models\Bullet;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
});

test('reflect page renders for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('reflect'))
        ->assertOk();
});

test('reflect shows empty state when no bullets exist', function () {
    $this->actingAs($this->user);

    Livewire::test('pages::bullets.reflect')
        ->assertSee('No bullets to reflect on.');
});

test('reflect shows active bullets in list', function () {
    $this->actingAs($this->user);
    Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'I am active',
    ]);

    Livewire::test('pages::bullets.reflect')
        ->assertSee('I am active');
});

test('reflect does not show expired bullets', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->expired()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'I am expired',
    ]);

    $bulletIds = Livewire::test('pages::bullets.reflect')
        ->get('bullets')->pluck('id')->toArray();

    expect($bulletIds)->not->toContain($bullet->id);
});

test('reflect does not show cancelled bullets', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->cancelled()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'I am cancelled',
    ]);

    $bulletIds = Livewire::test('pages::bullets.reflect')
        ->get('bullets')->pluck('id')->toArray();

    expect($bulletIds)->not->toContain($bullet->id);
});

test('reflect does not show migrated bullets', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->migrated()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'I am migrated',
    ]);

    $bulletIds = Livewire::test('pages::bullets.reflect')
        ->get('bullets')->pluck('id')->toArray();

    expect($bulletIds)->not->toContain($bullet->id);
});

test('reflect shows completed tasks', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->completed()->task()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'I am completed',
    ]);

    $bulletIds = Livewire::test('pages::bullets.reflect')
        ->get('bullets')->pluck('id')->toArray();

    expect($bulletIds)->toContain($bullet->id);
});

test('reflect does not show bullets from other teams', function () {
    $otherUser = User::factory()->create();
    Bullet::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
        'body' => 'Other team bullet',
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::bullets.reflect')
        ->assertDontSee('Other team bullet');
});

test('reflect orders by expires_at ascending', function () {
    $this->actingAs($this->user);
    $first = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'Least time left',
        'expires_at' => now()->addHours(10),
    ]);
    $second = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'body' => 'Most time left',
        'expires_at' => now()->addHours(72),
    ]);

    $bulletIds = Livewire::test('pages::bullets.reflect')
        ->get('bullets')->pluck('id')->toArray();

    expect($bulletIds)->toEqual([$first->id, $second->id]);
});

test('task can be completed from reflect page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->task()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.reflect')
        ->call('complete', $bullet->id);

    $bullet = $bullet->fresh();

    expect($bullet->status)->toEqual(BulletStatus::Completed)
        ->and($bullet->completed_at)->not->toBeNull();
});

test('bullet can be dropped from reflect page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->task()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.reflect')
        ->call('cancel', $bullet->id);

    expect($bullet->fresh()->status)->toEqual(BulletStatus::Cancelled);
});

test('bullet can be migrated from reflect page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.reflect')
        ->call('migrate', $bullet->id);

    expect($bullet->fresh()->status)->toEqual(BulletStatus::Migrated);
});

test('bullet can be removed from reflect page', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.reflect')
        ->call('remove', $bullet->id);

    expect(Bullet::find($bullet->id))->toBeNull();
});

test('acting on bullet from another team throws not found on reflect page', function () {
    $otherUser = User::factory()->create();
    $bullet = Bullet::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test('pages::bullets.reflect')
        ->call('remove', $bullet->id);
})->throws(ModelNotFoundException::class);

test('completing completed then migrated task preserves checkbox state', function () {
    $this->actingAs($this->user);
    $bullet = Bullet::factory()->task()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test('pages::bullets.reflect')
        ->call('complete', $bullet->id)
        ->call('migrate', $bullet->id);

    $bullet = $bullet->fresh();

    expect($bullet->status)->toEqual(BulletStatus::Migrated)
        ->and($bullet->completed_at)->not->toBeNull();
});
