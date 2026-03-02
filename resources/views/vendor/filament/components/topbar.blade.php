<x-filament::topbar
    :breadcrumbs="$breadcrumbs"
>
    {{-- Giữ nguyên phần mặc định --}}
    {{ $slot }}

    {{-- Hiển thị phông lưu trữ đang chọn --}}
    @if(session()->has('selected_archival_id'))
        <div class="ms-4 text-sm font-semibold text-primary-600">
            📁 {{ session('selected_archival_id') }}
        </div>
    @endif
</x-filament::topbar>
