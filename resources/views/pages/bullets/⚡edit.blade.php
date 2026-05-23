<?php

use App\Enums\BulletType;
use App\Models\Bullet;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public Bullet $bulletModel;

    public string $body = '';

    public string $type = 'task';

    public function mount(Bullet $bullet): void
    {
        if ($bullet->team_id !== Auth::user()->currentTeam->id) {
            abort(403);
        }

        $this->bulletModel = $bullet;
        $this->body = $bullet->body;
        $this->type = $bullet->type->value;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'body' => 'required|string|max:1000',
            'type' => ['required', 'string', Rule::in(BulletType::cases())],
        ]);

        $this->bulletModel->update([
            'body' => $validated['body'],
            'type' => $validated['type'],
        ]);

        Flux::toast(variant: 'success', text: 'Bullet updated.');

        $this->redirectRoute('bullets.index', navigate: true);
    }

    public function delete(): void
    {
        $this->bulletModel->delete();

        Flux::toast(variant: 'success', text: 'Bullet deleted.');

        $this->redirectRoute('bullets.index', navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Edit — ' . Str::limit($this->bulletModel->body, 30));
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit bullet</flux:heading>

    <form wire:submit="update" class="mt-6 space-y-6 max-w-xl">
        <flux:field>
            <flux:label>What's on your mind?</flux:label>
            <flux:input wire:model="body" required autofocus data-test="bullet-body-input" />
            <flux:error name="body" />
        </flux:field>

        <flux:field>
            <flux:label>Type</flux:label>
            <flux:radio.group variant="segmented" wire:model="type">
                <flux:radio value="task" icon="check-circle" data-test="type-task">Task</flux:radio>
                <flux:radio value="note" icon="light-bulb" data-test="type-note">Note</flux:radio>
            </flux:radio.group>
            <flux:error name="type" />
        </flux:field>

        <div class="flex">
            <flux:spacer />
            <flux:button variant="primary" type="submit" class="max-sm:w-full" data-test="create-bullet-submit">
                Save
            </flux:button>
        </div>
    </form>

    <div class="max-w-xl">
        <flux:separator class="my-8" />

        <div class="flex">
            <flux:spacer />
            <flux:button
                class="text-red-700! dark:text-red-300! max-sm:w-full"
                wire:click="delete"
                wire:confirm="Are you sure you want to delete this bullet?"
                data-test="bullet-delete-button"
            >
                Delete bullet
            </flux:button>
        </div>
    </div>
</section>
