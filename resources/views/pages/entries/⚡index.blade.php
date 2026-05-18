<?php

use App\Models\Entry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Entries')] class extends Component
{
    public string $sortField = 'expires_at';

    public string $sortDirection = 'desc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function entries()
    {
        $team = Auth::user()->currentTeam;

        return Entry::query()
            ->active()
            ->where('team_id', $team->id)
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Entries</flux:heading>
        <flux:button :href="route('entries.create')" wire:navigate data-test="new-entry-button">
            New entry
        </flux:button>
    </div>

    <div class="mt-8">
        <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column class="w-full"
                        sortable
                        :sorted="$sortField === 'content'"
                        :direction="$sortField === 'content' ? $sortDirection : null"
                        wire:click="sortBy('content')"
                    >
                        Content
                    </flux:table.column>
                    <flux:table.column
                        sortable
                        align="end"
                        :sorted="$sortField === 'expires_at'"
                        :direction="$sortField === 'expires_at' ? $sortDirection : null"
                        wire:click="sortBy('expires_at')"
                    >
                        Expires
                    </flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->entries as $entry)
                        <flux:table.row :key="$entry->id" data-test="entry-row">
                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('entries.show', $entry)" wire:navigate :first="true" aria-label="{{ $entry->content }}" />
                                <flux:badge color="zinc" size="sm" inset="top bottom">{{ $entry->type->label() }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('entries.show', $entry)" wire:navigate />
                                <span class="{{ $entry->is_completed ? 'line-through text-zinc-400 dark:text-zinc-600' : '' }}">
                                    {{ Str::limit($entry->content, 60) }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell class="relative" align="end">
                                <x-table-row-link :href="route('entries.show', $entry)" wire:navigate />
                                <span class="text-zinc-500">
                                    {{ $entry->timeRemaining() }}
                                </span>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
    </div>
</section>
