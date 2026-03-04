<x-filament-panels::page.simple>
    <style>
        /* Ẩn sidebar khi đang chọn phông dù session còn dữ liệu */
        .fi-sidebar-nav,
        .fi-sidebar-ctn {
            display: none !important;
        }
    </style>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-50">
        <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
            <h1 class="text-2xl font-bold text-center mb-6">Chọn loại phông làm việc</h1>

            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <x-filament::button type="submit" class="w-full">
                    Xác nhận
                </x-filament::button>
            </form>
        </div>
    </div>
</x-filament-panels::page.simple>
