<?php

use App\Enums\BulletStatus;
use App\Enums\BulletType;
use App\Models\Bullet;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reflect')] class extends Component
{
    #[Computed]
    public function bullets()
    {
        $team = Auth::user()->currentTeam;

        return Bullet::query()
            ->active()
            ->where('team_id', $team->id)
            ->whereNotIn('status', [BulletStatus::Cancelled, BulletStatus::Migrated])
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    public function complete(int $bulletId): void
    {
        $bullet = $this->findTeamBullet($bulletId);

        $bullet->update(['status' => BulletStatus::Completed, 'completed_at' => now()]);

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

    public function remove(int $bulletId): void
    {
        $bullet = $this->findTeamBullet($bulletId);

        $bullet->delete();

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
    @if ($this->bullets->isEmpty())
        <p class="lowercase">No bullets to reflect on.</p>
    @endif

    <ul role="list" class="divide-y divide-zinc-950/5 dark:divide-white/5">
        @foreach ($this->bullets as $bullet)
            <li :key="$bullet->id">
                <div class="py-2">
                    <div class="flex justify-between gap-x-6">
                        <div class="flex min-w-0 gap-x-1.5">
                            @if ($bullet->type === BulletType::Task)
                                <flux:checkbox
                                    :checked="filled($bullet->completed_at)"
                                    :disabled="filled($bullet->completed_at)"
                                    wire:click="complete({{ $bullet->id }})"
                                />
                            @else
                                <div class="h-6 flex items-center">
                                    <flux:icon.minus variant="micro" class="inline text-zinc-500 dark:text-zinc-400" />
                                </div>
                            @endif

                            <div class="min-w-0 flex-auto">
                                <p class="text-base/6 sm:text-sm/6 font-medium">{{ $bullet->body }}</p>
                            </div>
                        </div>

                        <span class="shrink-0">{{ $bullet->timeRemaining() }}</span>
                    </div>

                    <div class="mt-1 flex justify-start gap-1">
                        @if ($bullet->type === BulletType::Task && $bullet->status === BulletStatus::Active)
                            <flux:button variant="filled" size="xs" wire:click="cancel({{ $bullet->id }})" class="lowercase">Drop</flux:button>
                        @endif

                        <flux:button variant="filled" size="xs" wire:click="migrate({{ $bullet->id }})" class="lowercase">Migrate</flux:button>

                        <flux:button variant="filled" size="xs" wire:click="remove({{ $bullet->id }})" class="lowercase">Remove</flux:button>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</section>
