<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-950">Báo cáo tài liệu trong hồ sơ</h2>
                <p class="mt-1 text-sm text-gray-500">Dùng để kiểm tra chi tiết tài liệu trong từng hồ sơ.</p>
            </div>

            <div class="mb-3">
                <h3 class="text-base font-semibold text-gray-900">Thống kê</h3>
                <ul class="mt-2 list-disc pl-6 text-sm text-gray-600 space-y-1">
                    <li>Số tài liệu trong mỗi hồ sơ</li>
                    <li>Tổng số trang</li>
                    <li>Tài liệu thiếu / sai</li>
                </ul>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="text-sm text-gray-500">Tổng số hồ sơ</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($this->totalRecords, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="text-sm text-gray-500">Tổng số tài liệu</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($this->totalDocuments, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="text-sm text-gray-500">Tổng số trang</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($this->totalPages, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Hồ sơ</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Tiêu đề</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Tài liệu</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Trang</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Kiểm tra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->reportRows ?? [] as $row)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row['record_label'] }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $row['record_title'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['documents_count'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['document_pages'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $statusClass = match ($row['status']) {
                                            'Đủ' => 'bg-green-100 text-green-700',
                                            'Sai lệch số trang' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-red-100 text-red-700',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">Không có dữ liệu hồ sơ.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
