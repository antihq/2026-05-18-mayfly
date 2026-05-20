<?php

use App\Enums\EntryStatus;
use App\Enums\EntryType;
use App\Models\Entry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Entries')] class extends Component
{
    public string $newContent = '';

    #[Computed]
    public function entries()
    {
        $team = Auth::user()->currentTeam;

        return Entry::query()
            ->active()
            ->where('team_id', $team->id)
            ->orderBy('expires_at', 'desc')
            ->get();
    }

    #[Computed]
    public function recentlyExpired()
    {
        $team = Auth::user()->currentTeam;

        return Entry::query()
            ->expired()
            ->where('team_id', $team->id)
            ->where('expires_at', '>=', now()->subHours(24))
            ->latest('expires_at')
            ->get();
    }

    public function create(string $type): void
    {
        $validated = $this->validate([
            'newContent' => 'required|string|max:1000',
        ]);

        Entry::create([
            'team_id' => Auth::user()->currentTeam->id,
            'user_id' => Auth::user()->id,
            'type' => $type,
            'content' => $validated['newContent'],
            'status' => EntryStatus::Active,
            'expires_at' => now()->addHours(72),
        ]);

        $this->reset('newContent');

        unset($this->entries);
    }

    public function complete(int $entryId): void
    {
        $entry = $this->findTeamEntry($entryId);

        $entry->update(['status' => EntryStatus::Completed]);

        unset($this->entries);
    }

    public function cancel(int $entryId): void
    {
        $entry = $this->findTeamEntry($entryId);

        $entry->update(['status' => EntryStatus::Cancelled]);

        unset($this->entries);
    }

    public function migrate(int $entryId): void
    {
        $entry = $this->findTeamEntry($entryId);

        $entry->update(['status' => EntryStatus::Migrated]);

        unset($this->entries);
    }

    private function findTeamEntry(int $entryId): Entry
    {
        return Entry::query()
            ->where('team_id', Auth::user()->currentTeam->id)
            ->findOrFail($entryId);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading level="1">Recent entries</flux:heading>
    </div>

    <form wire:submit.prevent="create('task')" class="mt-6 flex flex-wrap items-start gap-x-4 gap-y-2">
        <flux:input wire:model="newContent" placeholder="Log a task or idea..." required data-test="inline-entry-input" class="min-w-0 sm:flex-1" />
        <div class="flex shrink-0 gap-x-2">
            <flux:button type="submit" data-test="inline-create-task">Log task</flux:button>
            <flux:button type="button" wire:click="create('note')" data-test="inline-create-note">Log note</flux:button>
        </div>
    </form>

    <flux:error name="newContent" />

    <div class="mt-6 flex gap-x-10">
        <div class="min-w-0 flex-1">
            <ul role="list" class="divide-y divide-zinc-950/5 dark:divide-white/10">
                @foreach ($this->entries as $entry)
                    <li :key="$entry->id" data-test="entry-row">
                        <div class="py-5">
                            <div class="flex justify-between gap-x-6">
                                <div class="flex min-w-0 gap-x-3">
                                    @if ($entry->status === EntryStatus::Migrated)
                                        <div class="h-6 flex items-center">
                                            <flux:icon.chevron-right variant="micro" class="inline text-zinc-500 dark:text-zinc-400" />
                                        </div>
                                    @endif

                                    @if ($entry->type === EntryType::Task)
                                        <flux:checkbox
                                            :checked="$entry->status === EntryStatus::Completed"
                                            :disabled="$entry->status === EntryStatus::Completed || $entry->status === EntryStatus::Cancelled || $entry->status === EntryStatus::Migrated"
                                            wire:click="complete({{ $entry->id }})"
                                            data-test="task-checkbox"
                                        />
                                    @endif

                                    <div class="min-w-0 flex-auto">
                                        <p class="text-base/6 sm:text-sm/6 font-medium {{ $entry->status === EntryStatus::Cancelled ? 'line-through text-zinc-500 dark:text-zinc-400' : ($entry->status === EntryStatus::Migrated ? 'text-zinc-500 dark:text-zinc-400' : '') }}">{{ $entry->content }}</p>
                                    </div>
                                </div>

                                <span class="shrink-0 text-sm/5 sm:text-xs/5 text-zinc-500 dark:text-zinc-400">
                                    {{ $entry->timeRemaining() }}
                                </span>
                            </div>

                            <div class="mt-1 flex justify-start gap-x-3 text-sm/5 sm:text-xs/5 text-zinc-500 dark:text-zinc-400">
                                @if ($entry->type === EntryType::Task && $entry->status === EntryStatus::Active)
                                    <button wire:click="cancel({{ $entry->id }})" data-test="cancel-button" class="text-zinc-500 dark:text-zinc-400 active:bg-yellow-100 lowercase">Cancel</button>
                                @endif

                                @if ($entry->status !== EntryStatus::Migrated && $entry->status !== EntryStatus::Cancelled)
                                    <button wire:click="migrate({{ $entry->id }})" data-test="migrate-button" class="text-zinc-500 dark:text-zinc-400 active:bg-yellow-100 lowercase">Migrate</button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($this->recentlyExpired->isNotEmpty())
            <aside class="hidden w-64 shrink-0 lg:block">
                <flux:heading class="text-zinc-500 dark:text-zinc-400">Recently expired</flux:heading>
                <ul role="list" class="divide-y divide-zinc-950/5 dark:divide-white/10">
                    @foreach ($this->recentlyExpired as $entry)
                        <li class="py-5">
                            <p class="truncate text-sm text-zinc-500 dark:text-zinc-400 font-medium">{{ Str::limit($entry->content, 40) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $entry->expires_at->diffForHumans() }}</p>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</section>
