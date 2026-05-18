<?php

use App\Models\Entry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
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

    #[Computed]
    public function isActive(): bool
    {
        return ! $this->entryModel->isExpired();
    }

    public function toggleComplete(): void
    {
        $this->entryModel->update([
            'is_completed' => ! $this->entryModel->is_completed,
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
                    {{ $entryModel->is_completed ? 'Reopen' : 'Complete' }}
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
            @if ($entryModel->is_completed)
                <flux:badge color="green" size="sm">Completed</flux:badge>
            @elseif ($this->isActive)
                <flux:badge color="blue" size="sm">Active</flux:badge>
            @else
                <flux:badge color="red" size="sm">Expired</flux:badge>
            @endif
        </x-description.details>

        <x-description.term>Content</x-description.term>
        <x-description.details>{{ $entryModel->content }}</x-description.details>

        <x-description.term>Author</x-description.term>
        <x-description.details>{{ $entryModel->user->name }}</x-description.details>

        <x-description.term>Expires</x-description.term>
        <x-description.details>
            {{ $entryModel->expires_at->format('M j, Y \a\t g:i a') }}
            <span class="ml-2 text-zinc-500">
                ({{ $entryModel->timeRemaining() }})
            </span>
        </x-description.details>

        <x-description.term>Created</x-description.term>
        <x-description.details>{{ $entryModel->created_at->format('M j, Y \a\t g:i a') }}</x-description.details>
    </x-description.list>
</section>
