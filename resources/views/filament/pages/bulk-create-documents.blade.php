<x-filament::page>
    <div class="max-w-2xl">
        <form wire:submit.prevent="createDocuments">
            {{ $this->form }}
            <x-filament::button type="submit" class="mt-4">
                Tạo nhiều văn bản
            </x-filament::button>
        </form>
    </div>
</x-filament::page>
