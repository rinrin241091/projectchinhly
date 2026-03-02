<div class="w-[90vw]"> 
<div class="p-6 bg-white rounded-lg shadow-lg max-h-[80vh] overflow-y-auto">
    <h2 class="text-xl font-semibold mb-4">Danh sách tài liệu trong hồ sơ: {{ $record->title }}</h2>
    
    @if($documents->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 border-b text-left">Mã tài liệu</th>
                        <th class="px-4 py-2 border-b text-left">Loại tài liệu</th>
                        <th class="px-4 py-2 border-b text-left">Mô tả</th>
                        <th class="px-4 py-2 border-b text-left">Tác giả</th>
                        <th class="px-4 py-2 border-b text-left">Số trang</th>
                        <th class="px-4 py-2 border-b text-left">Ngày tài liệu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b">{{ $document->document_code ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b">{{ $document->docType->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b">{{ $document->description }}</td>
                            <td class="px-4 py-2 border-b">{{ $document->author ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b">{{ $document->page_number ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b">{{ $document->document_date ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            Tổng số tài liệu: {{ $documents->count() }}
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <p>Không có tài liệu nào trong hồ sơ này.</p>
        </div>
    @endif
</div>
</div>
