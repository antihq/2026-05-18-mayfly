<?php

use App\Enums\BulletStatus;
use App\Enums\BulletType;
use App\Models\Bullet;
use Carbon\Carbon;
use Carbon\CarbonInterface;

test('scopeActive returns bullets that have not expired', function () {
    $active = Bullet::factory()->create(['expires_at' => now()->addHours(48)]);
    $expired = Bullet::factory()->create(['expires_at' => now()->subHour()]);

    $results = Bullet::active()->pluck('id');

    expect($results)->toContain($active->id);
    expect($results)->not->toContain($expired->id);
});

test('scopeExpired returns bullets past their expires_at', function () {
    $active = Bullet::factory()->create(['expires_at' => now()->addHours(48)]);
    $expired = Bullet::factory()->create(['expires_at' => now()->subHour()]);

    $results = Bullet::expired()->pluck('id');

    expect($results)->toContain($expired->id);
    expect($results)->not->toContain($active->id);
});

test('isExpired returns true when expires_at is in the past', function () {
    $bullet = Bullet::factory()->make(['expires_at' => now()->subMinute()]);

    expect($bullet->isExpired())->toBeTrue();
});

test('isExpired returns false when expires_at is in the future', function () {
    $bullet = Bullet::factory()->make(['expires_at' => now()->addHour()]);

    expect($bullet->isExpired())->toBeFalse();
});

test('timeRemaining returns Expired for past bullets', function () {
    $bullet = Bullet::factory()->make(['expires_at' => now()->subHour()]);

    expect($bullet->timeRemaining())->toEqual('Expired');
});

test('timeRemaining shows hours when more than a day remains', function () {
    Carbon::setTestNow('2026-05-20 12:00:00');
    $bullet = Bullet::factory()->make(['expires_at' => Carbon::parse('2026-05-22 14:00:00')]);

    expect($bullet->timeRemaining())->toEqual('50h left');
});

test('timeRemaining shows hours when less than a day remains', function () {
    Carbon::setTestNow('2026-05-20 12:00:00');
    $bullet = Bullet::factory()->make(['expires_at' => Carbon::parse('2026-05-20 17:30:00')]);

    expect($bullet->timeRemaining())->toEqual('5h left');
});

test('timeRemaining shows minutes when less than an hour remains', function () {
    Carbon::setTestNow('2026-05-20 12:00:00');
    $bullet = Bullet::factory()->make(['expires_at' => Carbon::parse('2026-05-20 12:30:00')]);

    expect($bullet->timeRemaining())->toEqual('30m left');
});

test('timeRemaining shows zero minutes when less than a minute remains', function () {
    Carbon::setTestNow('2026-05-20 12:00:00');
    $bullet = Bullet::factory()->make(['expires_at' => Carbon::parse('2026-05-20 12:00:10')]);

    expect($bullet->timeRemaining())->toEqual('0m left');
});

test('type is cast to BulletType enum', function () {
    $bullet = Bullet::factory()->create(['type' => BulletType::Task]);

    expect($bullet->type)->toBeInstanceOf(BulletType::class);
    expect($bullet->type)->toEqual(BulletType::Task);
});

test('expires_at is cast to datetime', function () {
    $bullet = Bullet::factory()->create();

    expect($bullet->expires_at)->toBeInstanceOf(CarbonInterface::class);
});

test('status defaults to active', function () {
    $bullet = Bullet::factory()->create();

    expect($bullet->status)->toEqual(BulletStatus::Active);
});

test('BulletType Task has correct icon and label', function () {
    expect(BulletType::Task->icon())->toEqual('check-circle')
        ->and(BulletType::Task->label())->toEqual('Task');
});

test('BulletType Note has correct icon and label', function () {
    expect(BulletType::Note->icon())->toEqual('light-bulb')
        ->and(BulletType::Note->label())->toEqual('Note');
});

test('BulletStatus has correct labels', function () {
    expect(BulletStatus::Active->label())->toEqual('Active')
        ->and(BulletStatus::Completed->label())->toEqual('Completed')
        ->and(BulletStatus::Cancelled->label())->toEqual('Cancelled')
        ->and(BulletStatus::Migrated->label())->toEqual('Migrated');
});

test('status is cast to BulletStatus enum', function () {
    $bullet = Bullet::factory()->create(['status' => BulletStatus::Completed]);

    expect($bullet->status)->toBeInstanceOf(BulletStatus::class)
        ->and($bullet->status)->toEqual(BulletStatus::Completed);
});

test('bullet belongs to a team', function () {
    $bullet = Bullet::factory()->create();

    expect($bullet->team)->not->toBeNull()
        ->and($bullet->team->id)->toEqual($bullet->team_id);
});

test('bullet belongs to a user', function () {
    $bullet = Bullet::factory()->create();

    expect($bullet->user)->not->toBeNull()
        ->and($bullet->user->id)->toEqual($bullet->user_id);
});
