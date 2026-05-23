<?php

use App\Enums\BulletStatus;
use App\Models\Bullet;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Bullet $bulletModel;

    public function mount(Bullet $bullet): void
    {
        if ($bullet->team_id !== Auth::user()->currentTeam->id) {
            abort(403);
        }

        $this->bulletModel = $bullet;
    }

    public function toggleComplete(): void
    {
        $this->bulletModel->update([
            'status' => $this->bulletModel->status === BulletStatus::Completed
                ? BulletStatus::Active
                : BulletStatus::Completed,
        ]);

        $this->redirectRoute('bullets.index', navigate: true);
    }

    public function render()
    {
        return $this->view()->title(Str::limit($this->bulletModel->body, 40));
    }
}; ?>

<section class="w-full">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <flux:heading size="xl" level="1">{{ Str::limit($bulletModel->body, 60) }}</flux:heading>
        <div class="flex items-center gap-2">
            @if ($bulletModel->type->value === 'task')
                <flux:button
                    wire:click="toggleComplete"
                    data-test="toggle-complete-button"
                >
                    {{ $bulletModel->status === BulletStatus::Completed ? 'Reopen' : 'Complete' }}
                </flux:button>
            @endif
            <flux:button :href="route('bullets.edit', $bulletModel)" wire:navigate data-test="bullet-edit-button">
                Edit
            </flux:button>
        </div>
    </div>

    <x-description.list class="mt-6">
        <x-description.term>Type</x-description.term>
        <x-description.details>
            <flux:badge color="zinc" size="sm">{{ $bulletModel->type->label() }}</flux:badge>
        </x-description.details>

        <x-description.term>Status</x-description.term>
        <x-description.details>
            <flux:badge color="zinc" size="sm">{{ $bulletModel->status->label() }}</flux:badge>
        </x-description.details>

        <x-description.term>Body</x-description.term>
        <x-description.details>{{ $bulletModel->body }}</x-description.details>

        <x-description.term>Author</x-description.term>
        <x-description.details>{{ $bulletModel->user->name }}</x-description.details>

        <x-description.term>Expires</x-description.term>
        <x-description.details>
            {{ $bulletModel->expires_at->format('M j, Y \a\t g:i a') }}
            <span class="ml-2 text-zinc-500 dark:text-zinc-400">
                ({{ $bulletModel->timeRemaining() }})
            </span>
        </x-description.details>

        <x-description.term>Created</x-description.term>
        <x-description.details>{{ $bulletModel->created_at->format('M j, Y \a\t g:i a') }}</x-description.details>
    </x-description.list>
</section>
