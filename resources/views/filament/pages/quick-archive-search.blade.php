<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950">Tiêu chí tìm kiếm</h2>
                    <p class="text-sm text-gray-500">Nhập một hoặc nhiều tiêu chí để tìm nhanh hồ sơ.</p>
                </div>

                <x-filament::button color="gray" wire:click="resetSearch">
                    Đặt lại
                </x-filament::button>
            </div>

            {{ $this->form }}
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
