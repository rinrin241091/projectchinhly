<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-950">Báo cáo thống kê hồ sơ</h2>
                <p class="mt-1 text-sm text-gray-500">Thống kê chi tiết hồ sơ đã chỉnh lý.</p>
            </div>

            <div class="mb-3">
                <h3 class="text-base font-semibold text-gray-900">Các tiêu chí</h3>
                <p class="mt-2 text-sm text-gray-600">Chọn radio button theo tiêu chí cần thống kê, bảng sẽ tự động tải lại ngay khi bạn click chọn.</p>
            </div>

            {{ $this->form }}
        </div>

        @if ($this->reportRows !== null)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-3 text-sm text-gray-600">
                    Chế độ: <span class="font-semibold text-gray-900">{{ $this->getModeLabel() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tiêu chí</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Số hồ sơ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->reportRows as $row)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-900">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['records_count'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-gray-400">Không có dữ liệu phù hợp.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (!empty($this->reportRows))
                            <tfoot>
                                <tr class="border-t-2 border-amber-300 bg-amber-50 font-semibold">
                                    <td class="px-4 py-3 text-right text-gray-900">TỔNG CỘNG</td>
                                    <td class="px-4 py-3 text-center text-gray-900">{{ number_format($this->totalRecords, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
