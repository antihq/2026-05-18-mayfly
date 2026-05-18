<?php

use App\Enums\EntryType;
use App\Models\Entry;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Entry')] class extends Component
{
    public string $content = '';

    public string $type = 'task';

    public function mount(): void
    {
        $this->type = EntryType::Task->value;
    }

    public function create(): void
    {
        $validated = $this->validate([
            'content' => 'required|string|max:1000',
            'type' => ['required', 'string', Rule::in(EntryType::cases())],
        ]);

        Entry::create([
            'team_id' => Auth::user()->currentTeam->id,
            'user_id' => Auth::user()->id,
            'type' => $validated['type'],
            'content' => $validated['content'],
            'is_completed' => false,
            'expires_at' => now()->addHours(72),
        ]);

        Flux::toast(variant: 'success', text: 'Entry created.');

        $this->redirectRoute('entries.index', navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">New entry</flux:heading>

    <form wire:submit="create" class="mt-6 space-y-6 max-w-xl">
        <flux:field>
            <flux:label>Content</flux:label>
            <flux:input wire:model="content" placeholder="Log a task or idea..." required autofocus data-test="entry-content-input" />
            <flux:error name="content" />
        </flux:field>

        <flux:field>
            <flux:label>Type</flux:label>
            <flux:radio.group variant="segmented" wire:model="type">
                <flux:radio value="task" icon="check-circle" data-test="type-task">Task</flux:radio>
                <flux:radio value="note" icon="light-bulb" data-test="type-note">Note</flux:radio>
            </flux:radio.group>
            <flux:error name="type" />
        </flux:field>

        <flux:button variant="primary" type="submit" data-test="create-entry-submit">
            Create entry
        </flux:button>
    </form>
</section>
