<?php

use App\Models\Entry;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Archive')] class extends Component
{
    public string $search = '';

    public string $filterType = '';

    #[Computed]
    public function recentGroups()
    {
        $team = Auth::user()->currentTeam;

        return Entry::query()
            ->expired()
            ->where('team_id', $team->id)
            ->latest('expires_at')
            ->limit(50)
            ->get()
            ->groupBy(fn (Entry $entry) => $entry->expires_at->format('Y-m-d'))
            ->take(2);
    }

    #[Computed]
    public function allEntries()
    {
        $team = Auth::user()->currentTeam;

        $query = Entry::query()
            ->expired()
            ->where('team_id', $team->id)
            ->latest('expires_at');

        if ($this->search) {
            $query->where('content', 'like', '%'.$this->search.'%');
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        return $query->paginate(15);
    }

    public function formatDateGroup(string $date): string
    {
        $carbon = \Carbon\Carbon::parse($date);

        if ($carbon->isToday()) {
            return 'Expired today';
        }

        if ($carbon->isYesterday()) {
            return 'Expired yesterday';
        }

        return 'Expired '.$carbon->format('M j, Y');
    }

    public function restore(int $entryId): void
    {
        $entry = Entry::expired()
            ->where('team_id', Auth::user()->currentTeam->id)
            ->findOrFail($entryId);

        $entry->update(['expires_at' => now()->addHours(72)]);

        unset($this->recentGroups, $this->allEntries);

        Flux::toast(variant: 'success', text: 'Entry restored with a fresh 72h timer.');
    }

    public function destroy(int $entryId): void
    {
        $entry = Entry::expired()
            ->where('team_id', Auth::user()->currentTeam->id)
            ->findOrFail($entryId);

        $entry->delete();

        unset($this->recentGroups, $this->allEntries);

        Flux::toast(variant: 'success', text: 'Entry permanently deleted.');
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Archive</flux:heading>
    </div>

    @if ($this->recentGroups->isNotEmpty())
        <div class="mt-8 space-y-8">
            @foreach ($this->recentGroups as $dateGroup => $groupEntries)
                <div>
                    <flux:heading size="sm" class="mb-2">
                        {{ $this->formatDateGroup($dateGroup) }}
                    </flux:heading>

                    <flux:table bleed>
                        <flux:table.columns>
                            <flux:table.column>Type</flux:table.column>
                            <flux:table.column class="w-full">Content</flux:table.column>
                            <flux:table.column align="end">Expired</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($groupEntries as $entry)
                                <flux:table.row :key="$entry->id">
                                    <flux:table.cell class="relative">
                                        <x-table-row-link :href="route('entries.show', $entry)" wire:navigate :first="true" aria-label="{{ $entry->content }}" />
                                        <flux:badge color="zinc" size="sm" inset="top bottom">{{ $entry->type->label() }}</flux:badge>
                                    </flux:table.cell>

                                    <flux:table.cell class="relative">
                                        <x-table-row-link :href="route('entries.show', $entry)" wire:navigate />
                                        <span class="text-zinc-500 {{ $entry->is_completed ? 'line-through' : '' }}">
                                            {{ Str::limit($entry->content, 60) }}
                                        </span>
                                    </flux:table.cell>

                                    <flux:table.cell class="relative" align="end">
                                        <x-table-row-link :href="route('entries.show', $entry)" wire:navigate />
                                        <span class="text-zinc-500">
                                            {{ $entry->expires_at->format('M j, g:i a') }}
                                        </span>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endforeach
        </div>

        <flux:separator class="mt-8" />
    @endif

    <div class="mt-8">
        <flux:heading size="lg" level="2">All entries</flux:heading>

        <div class="mt-4 flex gap-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search entries..."
                icon="magnifying-glass"
                class="flex-1"
            />
            <flux:select wire:model.live="filterType" class="w-32">
                <option value="">All types</option>
                @foreach (App\Enums\EntryType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}s</option>
                @endforeach
            </flux:select>
        </div>

        <flux:table bleed class="mt-4">
                <flux:table.columns>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column class="w-full">Content</flux:table.column>
                    <flux:table.column align="end">Expired</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->allEntries as $entry)
                        <flux:table.row :key="$entry->id">
                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('entries.show', $entry)" wire:navigate :first="true" aria-label="{{ $entry->content }}" />
                                <flux:badge color="zinc" size="sm" inset="top bottom">{{ $entry->type->label() }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('entries.show', $entry)" wire:navigate />
                                <span class="text-zinc-500 {{ $entry->is_completed ? 'line-through' : '' }}">
                                    {{ Str::limit($entry->content, 60) }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell class="relative" align="end">
                                <x-table-row-link :href="route('entries.show', $entry)" wire:navigate />
                                <span class="text-zinc-500">
                                    {{ $entry->expires_at->format('M j, g:i a') }}
                                </span>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            {{ $this->allEntries->links() }}
    </div>
</section>
