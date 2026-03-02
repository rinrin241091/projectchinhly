<x-filament::page>
    <div class="max-w-2xl">
        <form wire:submit.prevent="createShelves">
            {{ $this->form }}
            <x-filament::button type="submit" class="mt-4">
                Tạo nhiều kệ
            </x-filament::button>
        </form>
    </div>
</x-filament::page>
