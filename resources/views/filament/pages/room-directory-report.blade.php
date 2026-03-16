<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-950">Báo cáo danh mục phông</h2>
                <p class="mt-1 text-sm text-gray-500">Thống kê các phông lưu trữ: số hồ sơ và khoảng thời gian tài liệu.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="text-sm text-gray-500">Tổng số phông</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($this->totalRooms, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="text-sm text-gray-500">Tổng số hồ sơ</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($this->totalRecords, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Phông</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Số hồ sơ</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->reportRows ?? [] as $i => $row)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">
                                    {{ number_format($row['records_count'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $row['time_range'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400">Chưa có dữ liệu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (!empty($this->reportRows))
                        <tfoot>
                            <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold">
                                <td class="px-4 py-3 text-gray-700" colspan="2">Tổng cộng</td>
                                <td class="px-4 py-3 text-center text-gray-900">
                                    {{ number_format($this->totalRecords, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
