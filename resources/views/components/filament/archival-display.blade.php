@php
    $archivalId = session('selected_archival_id');
    $user = auth()->user();

    $archival = $archivalId
        ? \Illuminate\Support\Facades\Cache::remember(
            'topbar:org:selected:' . $archivalId,
            now()->addMinutes(5),
            fn () => \App\Models\Organization::query()->select('id', 'name')->find($archivalId)
        )
        : null;
    
    // Lấy danh sách phông mà user có quyền truy cập
    if (in_array($user->role, ['admin', 'super_admin'])) {
        // Admin / super_admin có thể thấy tất cả phông
        $availableOrganizations = \Illuminate\Support\Facades\Cache::remember(
            'topbar:org:list:admin',
            now()->addMinutes(5),
            fn () => \App\Models\Organization::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    } else {
        // Non-admin chỉ thấy những phông được gán
        $availableOrganizations = \Illuminate\Support\Facades\Cache::remember(
            'topbar:org:list:user:' . $user->id,
            now()->addMinutes(2),
            fn () => $user->organizations()
                ->select('organizations.id', 'organizations.name')
                ->orderBy('organizations.name')
                ->get()
        );
    }
@endphp

@if($archival)
<div class="flex items-center space-x-2">

    {{-- Phông đang chọn --}}
    <div class="flex items-center space-x-2 rounded-lg bg-green-600 px-3 py-2 text-white">
        <span class="text-sm font-semibold">
            Phông đang chọn: {{ $archival->name }}
        </span>
    </div>

    {{-- Tất cả users có thể dùng dropdown select phông --}}
    <div class="flex items-center space-x-2">
        <select 
            id="switchOrganization"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white hover:bg-gray-50"
            onchange="changeOrganization(this.value)"
        >
            <option value="">-- Chọn phông để chuyển --</option>
            @foreach($availableOrganizations as $org)
                <option value="{{ $org->id }}" @if($org->id == $archivalId) selected @endif>
                    {{ $org->name }}
                </option>
            @endforeach
        </select>

        {{-- Chỉ ADMIN mới được nút Chọn lại phông --}}
        @if(in_array($user->role, ['admin', 'super_admin']))
            <button
                onclick="window.location.href = '{{ route('filament.dashboard.pages.select-organization') }}';"
                class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700"
            >
                Chọn lại phông
            </button>
        @endif
    </div>

</div>

<script>
function changeOrganization(organizationId) {
    if (organizationId) {
        // Cập nhật session rồi điều hướng mềm để tránh full reload toàn trang.
        fetch('{{ route('change-organization') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                organization_id: organizationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Lỗi khi chuyển phông');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Lỗi khi chuyển phông');
        });
    }
}
</script>
@endif
