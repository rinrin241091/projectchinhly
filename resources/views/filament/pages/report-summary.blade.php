<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Criteria card --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Tiêu chí báo cáo</h2>
                <p class="mt-1 text-sm text-gray-500">Chọn phạm vi thời gian và phông để tổng hợp số liệu chỉnh lý.</p>
            </div>
            {{ $this->form }}
        </div>

        {{-- Report table --}}
        @if ($this->reportRows !== null)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-700 dark:bg-gray-900">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-950 uppercase dark:text-white">
                        BÁO CÁO TỔNG HỢP KHỐI LƯỢNG CHỈNH LÝ HỒ SƠ
                    </h2>
                    @php
                        $df = $this->appliedFilters['date_from'] ?? null;
                        $dt = $this->appliedFilters['date_to']   ?? null;
                    @endphp
                    @if ($df || $dt)
                        <p class="mt-1 text-sm text-gray-500">
                            Giai đoạn:
                            @if ($df) Từ {{ \Carbon\Carbon::parse($df)->format('d/m/Y') }} @endif
                            @if ($dt) đến {{ \Carbon\Carbon::parse($dt)->format('d/m/Y') }} @endif
                        </p>
                    @else
                        <p class="mt-1 text-sm text-gray-400">Tổng hợp toàn bộ thời gian</p>
                    @endif
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                                <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300 w-12">STT</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Tên phông lưu trữ</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Số hồ sơ<br>chỉnh lý</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Số văn bản<br>/tài liệu</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Số hộp</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Tổng số trang</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Mét giá (m)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->reportRows as $row)
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 text-center text-gray-500">{{ $row['stt'] }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ number_format($row['records_count'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ number_format($row['documents_count'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ number_format($row['boxes_count'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ number_format($row['total_pages'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ number_format($row['met_gia'], 3, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                                        Không có dữ liệu phù hợp với tiêu chí đã chọn.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (!empty($this->reportRows))
                            <tfoot>
                                <tr class="border-t-2 border-amber-400 bg-amber-50 font-bold dark:bg-amber-900/20">
                                    <td colspan="2" class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                        TỔNG CỘNG
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                        {{ number_format($this->reportTotals['records_count'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                        {{ number_format($this->reportTotals['documents_count'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                        {{ number_format($this->reportTotals['boxes_count'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                        {{ number_format($this->reportTotals['total_pages'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                        {{ number_format($this->reportTotals['met_gia'], 3, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-14 text-center dark:border-gray-700 dark:bg-gray-900">
                <x-heroicon-o-document-chart-bar class="mx-auto h-14 w-14 text-gray-300" />
                <p class="mt-4 text-lg font-medium text-gray-500">Chưa có dữ liệu báo cáo</p>
                <p class="text-sm text-gray-400 mt-1">Chọn tiêu chí rồi nhấn <strong class="text-primary-600">Xem báo cáo</strong> để tổng hợp số liệu.</p>
            </div>
        @endif

    </div>
</x-filament-panels::page>
