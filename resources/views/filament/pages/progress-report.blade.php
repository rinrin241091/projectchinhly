<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-950">Báo cáo tiến độ chỉnh lý</h2>
                <p class="mt-1 text-sm text-gray-500">Dùng để theo dõi tiến độ thi công dự án theo ngày, tháng, đơn vị thực hiện hoặc nhân sự.</p>
            </div>

            <div class="mb-3">
                <h3 class="text-base font-semibold text-gray-900">Thống kê</h3>
                <p class="mt-2 text-sm text-gray-600">Chọn kiểu thống kê bằng radio button, bảng sẽ tự động cập nhật ngay sau khi bạn chọn.</p>
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
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Mốc thống kê</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Hồ sơ chỉnh lý</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Tài liệu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->reportRows as $row)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-900">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['records_count'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['documents_count'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-400">Không có dữ liệu phù hợp.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (!empty($this->reportRows))
                            <tfoot>
                                <tr class="border-t-2 border-amber-300 bg-amber-50 font-semibold">
                                    <td class="px-4 py-3 text-right text-gray-900">TỔNG CỘNG</td>
                                    <td class="px-4 py-3 text-center text-gray-900">{{ number_format($this->reportTotals['records_count'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center text-gray-900">{{ number_format($this->reportTotals['documents_count'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
