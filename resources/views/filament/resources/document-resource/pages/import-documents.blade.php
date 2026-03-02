<x-filament::page>
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Import Tài Liệu Từ CSV</h2>
            
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
                                <p>File CSV cần có các cột sau:</p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li><strong>document_code</strong>: Số ký hiệu văn bản (bắt buộc)</li>
                                    <li><strong>document_date</strong>: Ngày tháng văn bản (YYYY-MM-DD)</li>
                                    <li><strong>description</strong>: Trích yếu nội dung (bắt buộc)</li>
                                    <li><strong>author</strong>: Tác giả</li>
                                    <li><strong>page_number</strong>: Số tờ</li>
                                    <li><strong>note</strong>: Ghi chú</li>
                                    <li><strong>archive_record_reference</strong>: Mã hồ sơ liên kết (bắt buộc)</li>
                                    <li><strong>doc_type_name</strong>: Tên loại văn bản (bắt buộc)</li>
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
                                    <li>Mã hồ sơ liên kết phải tồn tại trong hệ thống1</li>
                                    <li>Loại văn bản sẽ được tự động tạo nếu chưa tồn tại</li>
                                    <li>File CSV nên có dung lượng dưới 10MB</li>
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
                            <p>File <strong>{{ session('pending_import.original_name') }}</strong> đã được lưu thành công và sẵn sàng để import.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-filament::page>
