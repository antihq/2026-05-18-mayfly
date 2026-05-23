<?php

use App\Enums\BulletStatus;
use App\Models\Bullet;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Bullet')] class extends Component
{
    public string $body = '';

    public function create(string $type): void
    {
        $validated = $this->validate([
            'body' => 'required|string|max:1000',
        ]);

        Bullet::create([
            'team_id' => Auth::user()->currentTeam->id,
            'user_id' => Auth::user()->id,
            'type' => $type,
            'body' => $validated['body'],
            'status' => BulletStatus::Active,
            'expires_at' => now()->addHours(72),
        ]);

        Flux::toast(variant: 'success', text: 'Bullet created.');

        $this->redirectRoute('bullets.index', navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">New bullet</flux:heading>

    <form wire:submit.prevent="create('task')" class="mt-6 space-y-6 max-w-xl">
        <flux:field>
            <flux:label>What's on your mind?</flux:label>
            <flux:input wire:model="body" required autofocus data-test="bullet-body-input" />
            <flux:error name="body" />
        </flux:field>

        <div class="flex gap-x-2">
            <flux:spacer />
            <flux:button type="submit" data-test="create-bullet-task">Log task</flux:button>
            <flux:button type="button" wire:click="create('note')" data-test="create-bullet-note">Log note</flux:button>
        </div>
    </form>
</section>
