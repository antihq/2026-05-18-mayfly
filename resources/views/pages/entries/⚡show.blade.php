<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Entry $entryModel;

    public function mount(Entry $entry): void
    {
        if ($entry->team_id !== Auth::user()->currentTeam->id) {
            abort(403);
        }

        $this->entryModel = $entry;
    }

    public function toggleComplete(): void
    {
        $this->entryModel->update([
            'status' => $this->entryModel->status === EntryStatus::Completed
                ? EntryStatus::Active
                : EntryStatus::Completed,
        ]);

        $this->redirectRoute('entries.index', navigate: true);
    }

    public function render()
    {
        return $this->view()->title(Str::limit($this->entryModel->content, 40));
    }
}; ?>

<section class="w-full">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <flux:heading size="xl" level="1">{{ Str::limit($entryModel->content, 60) }}</flux:heading>
        <div class="flex items-center gap-2">
            @if ($entryModel->type->value === 'task')
                <flux:button
                    wire:click="toggleComplete"
                    data-test="toggle-complete-button"
                >
                    {{ $entryModel->status === EntryStatus::Completed ? 'Reopen' : 'Complete' }}
                </flux:button>
            @endif
            <flux:button :href="route('entries.edit', $entryModel)" wire:navigate data-test="entry-edit-button">
                Edit
            </flux:button>
        </div>
    </div>

    <x-description.list class="mt-6">
        <x-description.term>Type</x-description.term>
        <x-description.details>
            <flux:badge color="zinc" size="sm">{{ $entryModel->type->label() }}</flux:badge>
        </x-description.details>

        <x-description.term>Status</x-description.term>
        <x-description.details>
            <flux:badge color="zinc" size="sm">{{ $entryModel->status->label() }}</flux:badge>
        </x-description.details>

        <x-description.term>Content</x-description.term>
        <x-description.details>{{ $entryModel->content }}</x-description.details>

        <x-description.term>Author</x-description.term>
        <x-description.details>{{ $entryModel->user->name }}</x-description.details>

        <x-description.term>Expires</x-description.term>
        <x-description.details>
            {{ $entryModel->expires_at->format('M j, Y \a\t g:i a') }}
            <span class="ml-2 text-zinc-500 dark:text-zinc-400">
                ({{ $entryModel->timeRemaining() }})
            </span>
        </x-description.details>

        <x-description.term>Created</x-description.term>
        <x-description.details>{{ $entryModel->created_at->format('M j, Y \a\t g:i a') }}</x-description.details>
    </x-description.list>
</section>
