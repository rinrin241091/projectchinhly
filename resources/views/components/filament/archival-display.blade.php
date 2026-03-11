@php
    $archivalId = session('selected_archival_id');
    $archival = $archivalId ? \App\Models\Organization::find($archivalId) : null;
    $user = auth()->user();
    
    // Lấy danh sách phông mà user có quyền truy cập
    if ($user->role === 'admin') {
        // Admin có thể thấy tất cả phông
        $availableOrganizations = \App\Models\Organization::all();
    } else {
        // Non-admin chỉ thấy những phông được gán
        $availableOrganizations = $user->organizations;
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
        @if($user->role === 'admin')
            <button
                onclick="window.location='{{ route('filament.dashboard.pages.select-organization') }}'"
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
        // Gửi AJAX request để cập nhật session và reload trang
        fetch('{{ route('change-organization') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
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
