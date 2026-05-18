<?php

use App\Enums\EntryType;
use App\Models\Entry;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public Entry $entryModel;

    public string $content = '';

    public string $type = 'task';

    public function mount(Entry $entry): void
    {
        if ($entry->team_id !== Auth::user()->currentTeam->id) {
            abort(403);
        }

        $this->entryModel = $entry;
        $this->content = $entry->content;
        $this->type = $entry->type->value;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'content' => 'required|string|max:1000',
            'type' => ['required', 'string', Rule::in(EntryType::cases())],
        ]);

        $this->entryModel->update([
            'content' => $validated['content'],
            'type' => $validated['type'],
        ]);

        Flux::toast(variant: 'success', text: 'Entry updated.');

        $this->redirectRoute('entries.index', navigate: true);
    }

    public function delete(): void
    {
        $this->entryModel->delete();

        Flux::toast(variant: 'success', text: 'Entry deleted.');

        $this->redirectRoute('entries.index', navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Edit — ' . Str::limit($this->entryModel->content, 30));
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit entry</flux:heading>

    <form wire:submit="update" class="mt-6 space-y-6 max-w-xl">
        <flux:field>
            <flux:label>Content</flux:label>
            <flux:input wire:model="content" required autofocus data-test="entry-content-input" />
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

        <div class="flex">
            <flux:spacer />
            <flux:button variant="primary" type="submit" class="max-sm:w-full" data-test="create-entry-submit">
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
                wire:confirm="Are you sure you want to delete this entry?"
                data-test="entry-delete-button"
            >
                Delete entry
            </flux:button>
        </div>
    </div>
</section>
