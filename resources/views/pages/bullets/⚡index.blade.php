<?php

use App\Enums\BulletStatus;
use App\Enums\BulletType;
use App\Models\Bullet;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bullets')] class extends Component
{
    public string $newBody = '';

    #[Computed]
    public function bullets()
    {
        $team = Auth::user()->currentTeam;

        return Bullet::query()
            ->active()
            ->where('team_id', $team->id)
            ->orderBy('expires_at', 'desc')
            ->get();
    }

    public function create(string $type): void
    {
        $validated = $this->validate([
            'newBody' => 'required|string|max:1000',
        ]);

        Bullet::create([
            'team_id' => Auth::user()->currentTeam->id,
            'user_id' => Auth::user()->id,
            'type' => $type,
            'body' => $validated['newBody'],
            'status' => BulletStatus::Active,
            'expires_at' => now()->addHours(72),
        ]);

        $this->reset('newBody');

        unset($this->bullets);
    }

    public function complete(int $bulletId): void
    {
        $bullet = $this->findTeamBullet($bulletId);

        $bullet->update(['status' => BulletStatus::Completed]);

        unset($this->bullets);
    }

    public function cancel(int $bulletId): void
    {
        $bullet = $this->findTeamBullet($bulletId);

        $bullet->update(['status' => BulletStatus::Cancelled]);

        unset($this->bullets);
    }

    public function migrate(int $bulletId): void
    {
        $bullet = $this->findTeamBullet($bulletId);

        $bullet->update(['status' => BulletStatus::Migrated]);

        unset($this->bullets);
    }

    private function findTeamBullet(int $bulletId): Bullet
    {
        return Bullet::query()
            ->where('team_id', Auth::user()->currentTeam->id)
            ->findOrFail($bulletId);
    }
}; ?>

<section class="max-w-2xl">
    <form wire:submit.prevent="create('task')">
        <flux:field class="max-w-sm">
            <flux:label class="lowercase">What's on your mind?</flux:label>
            <flux:input wire:model="newBody" required data-test="inline-bullet-input" autofocus />
            <flux:error name="newBody" />
        </flux:field>

        <div class="mt-4 flex gap-1">
            <flux:button type="submit" variant="primary" color="lime" class="lowercase" data-test="inline-create-task">Log task</flux:button>
            <flux:button type="button" wire:click="create('note')" variant="primary" color="lime" class="lowercase" data-test="inline-create-note">Log note</flux:button>
        </div>
    </form>

    <ul role="list" class="mt-6 divide-y divide-zinc-950/5 dark:divide-white/5">
        @foreach ($this->bullets as $bullet)
            <li :key="$bullet->id" data-test="bullet-row">
                <div class="py-2">
                    <div class="flex justify-between gap-x-6">
                        <div class="flex min-w-0 gap-x-3">
                            @if ($bullet->status === BulletStatus::Migrated)
                                <div class="h-6 flex items-center">
                                    <flux:icon.chevron-right variant="micro" class="inline text-zinc-500 dark:text-zinc-400" />
                                </div>
                            @endif

                            @if ($bullet->type === BulletType::Task)
                                <flux:checkbox
                                    :checked="$bullet->status === BulletStatus::Completed"
                                    :disabled="$bullet->status === BulletStatus::Completed || $bullet->status === BulletStatus::Cancelled || $bullet->status === BulletStatus::Migrated"
                                    wire:click="complete({{ $bullet->id }})"
                                    data-test="task-checkbox"
                                />
                            @endif

                            <div class="min-w-0 flex-auto">
                                <p class="text-base/6 sm:text-sm/6 font-medium {{ $bullet->status === BulletStatus::Cancelled ? 'line-through text-zinc-500 dark:text-zinc-400' : ($bullet->status === BulletStatus::Migrated ? 'text-zinc-500 dark:text-zinc-400' : '') }}">{{ $bullet->body }}</p>
                            </div>
                        </div>

                        <span class="shrink-0 text-sm/5 sm:text-xs/5 text-zinc-500 dark:text-zinc-400">
                            {{ $bullet->timeRemaining() }}
                        </span>
                    </div>

                    <div class="mt-1 flex justify-start gap-x-3 text-sm/5 sm:text-xs/5 text-zinc-500 dark:text-zinc-400">
                        @if ($bullet->type === BulletType::Task && $bullet->status === BulletStatus::Active)
                            <button wire:click="cancel({{ $bullet->id }})" data-test="cancel-button" class="text-zinc-500 dark:text-zinc-400 active:bg-yellow-100 lowercase">Cancel</button>
                        @endif

                        @if ($bullet->status !== BulletStatus::Migrated && $bullet->status !== BulletStatus::Cancelled)
                            <button wire:click="migrate({{ $bullet->id }})" data-test="migrate-button" class="text-zinc-500 dark:text-zinc-400 active:bg-yellow-100 lowercase">Migrate</button>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</section>
