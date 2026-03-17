<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-950">Sao lưu dữ liệu kho</h2>
                <p class="mt-1 text-sm text-gray-500">Chọn kho để sao lưu ngay hoặc đặt lịch sao lưu theo giờ hằng ngày.</p>
            </div>

            <div class="mb-3 text-sm text-gray-700">
                <div>- Sao lưu dữ liệu kho</div>
                <div>- Đặt lịch giờ sao lưu dữ liệu</div>
            </div>

            {{ $this->form }}
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950">Danh sách lịch sao lưu</h3>
                <p class="mt-1 text-sm text-gray-500">Xem, nạp để chỉnh sửa hoặc xóa lịch sao lưu ngay tại đây.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Kho</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Giờ sao lưu</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Trạng thái</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Lần chạy gần nhất</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->schedules as $schedule)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900">{{ $schedule->storage?->name ?? ('#' . $schedule->storage_id) }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ \Carbon\Carbon::parse($schedule->backup_time)->format('H:i') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($schedule->is_active)
                                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Đang bật</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">Đang tắt</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700">
                                    {{ $schedule->last_run_at ? $schedule->last_run_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="editSchedule({{ $schedule->id }})"
                                            class="rounded-md bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                        >
                                            Sửa
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="deleteSchedule({{ $schedule->id }})"
                                            wire:confirm="Bạn có chắc chắn muốn xóa lịch sao lưu này?"
                                            class="rounded-md bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                        >
                                            Xóa
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">Chưa có lịch sao lưu nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
