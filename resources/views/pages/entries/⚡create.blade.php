<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Entry')] class extends Component
{
    public string $content = '';

    public function create(string $type): void
    {
        $validated = $this->validate([
            'content' => 'required|string|max:1000',
        ]);

        Entry::create([
            'team_id' => Auth::user()->currentTeam->id,
            'user_id' => Auth::user()->id,
            'type' => $type,
            'content' => $validated['content'],
            'status' => EntryStatus::Active,
            'expires_at' => now()->addHours(72),
        ]);

        Flux::toast(variant: 'success', text: 'Entry created.');

        $this->redirectRoute('entries.index', navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">New entry</flux:heading>

    <form wire:submit.prevent="create('task')" class="mt-6 space-y-6 max-w-xl">
        <flux:field>
            <flux:label>Content</flux:label>
            <flux:input wire:model="content" placeholder="Log a task or idea..." required autofocus data-test="entry-content-input" />
            <flux:error name="content" />
        </flux:field>

        <div class="flex gap-x-2">
            <flux:spacer />
            <flux:button type="submit" data-test="create-entry-task">Log task</flux:button>
            <flux:button type="button" wire:click="create('note')" data-test="create-entry-note">Log note</flux:button>
        </div>
    </form>
</section>
