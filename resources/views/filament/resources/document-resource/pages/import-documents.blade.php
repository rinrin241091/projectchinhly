<x-filament::page>
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Import Tài Liệu</h2>
                <button
                    wire:click="downloadTemplate"
                    class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Tải mẫu Excel
                </button>
            </div>
            
            <div class="mb-6">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Hướng dẫn import</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p class="mb-2">Hỗ trợ file <strong>Excel (.xlsx)</strong> và <strong>CSV</strong>. Bấm <strong>"Tải mẫu Excel"</strong> để tải file mẫu.</p>
                                <p class="mb-1">Các cột trong file mẫu giống với file xuất Excel:</p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li><strong>Số, Ký hiệu</strong>: Số ký hiệu văn bản</li>
                                    <li><strong>Ngày tháng văn bản</strong>: Định dạng dd/mm/yyyy</li>
                                    <li><strong>Trích yếu nội dung</strong>: Nội dung trích yếu</li>
                                    <li><strong>Tác giả</strong>: Tác giả văn bản</li>
                                    <li><strong>Tờ số / Trang số</strong>: Số tờ (VD: 1 - 5)</li>
                                    <li><strong>Ghi chú</strong>: Ghi chú</li>
                                    <li><strong>Mã hồ sơ</strong>: Mã hoặc ID hồ sơ liên kết <span class="text-red-600 font-semibold">(bắt buộc)</span></li>
                                    <li><strong>Loại văn bản</strong>: Tên loại văn bản (tự tạo nếu chưa có)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Lưu ý</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Bạn có thể dùng file Excel đã xuất từ hệ thống, thêm 2 cột <strong>"Mã hồ sơ"</strong> và <strong>"Loại văn bản"</strong> rồi import lại</li>
                                    <li>Mã hồ sơ có thể là ID, mã hồ sơ (code), hoặc mã tham chiếu (reference_code)</li>
                                    <li>Loại văn bản sẽ được tự động tạo nếu chưa tồn tại</li>
                                    <li>Dòng ví dụ (chữ nghiêng màu xám) trong file mẫu sẽ được bỏ qua tự động</li>
                                    <li>File nên có dung lượng dưới 10MB</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                {{ $this->form }}
            </div>

            <div class="mt-6 flex justify-end space-x-4">
            <x-filament::button
                wire:click="save"
                color="success"
                size="sm"
            >
                Lưu File
            </x-filament::button>

            <x-filament::button
                wire:click="import"
                color="primary"
                size="sm"
            >
                Import Dữ Liệu
            </x-filament::button>
        </div>

            @if(session()->has('pending_import'))
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">File đã được lưu</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p><strong>{{ session('pending_import.file_count', 1) }}</strong> file đã được lưu thành công và sẵn sàng để import.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-filament::page>
