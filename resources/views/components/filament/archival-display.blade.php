@php
    $archivalId = session('selected_archival_id');
    $archival = $archivalId ? \App\Models\Organization::find($archivalId) : null;
@endphp

@if($archival)
    <div class="flex items-center space-x-2">
        <div class="flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 dark:bg-blue-900/20">
            <div>
                <div class="text-xs font-medium text-blue-600 dark:text-blue-400">Phông đang chọn</div>
                <div class="text-sm font-semibold text-blue-800 dark:text-blue-200">{{ $archival->name }}</div>
            </div>
        </div>
    </div>
@endif
