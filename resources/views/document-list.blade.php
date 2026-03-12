<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách tài liệu - Hồ sơ {{ $archiveRecord->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #e9ecef;
        }
        .export-button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }
        .export-button:hover {
            background-color: #0056b3;
        }
        
        /* Hide elements with no-print class during printing */
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    @php
        $isPartyOrganization = $archiveRecord->organization?->type === 'Đảng';
    @endphp

    <h3 style="text-align: center">MỤC LỤC VĂN BẢN, TÀI LIỆU</h3>
    <b><p style="text-align: center; margin-bottom: 40px;">{{ $archiveRecord->title }}</p></b>
    <p style="text-align: center; margin-bottom: 40px;">Hộp số: {{ $archiveRecord->box->code ?? '' }}</p>
    
    <div style="display: flex; justify-content: center; gap: 20px; margin: 20px auto;" class="no-print">
        <button onclick="window.print()" class="export-button" style="border: none; cursor: pointer;">In mục lục tài liệu</button>
        <a href="{{ route('archive-records.documents.export-excel', $archiveRecord->id) }}" class="export-button" style="background-color: #28a745;" target="_blank">Xuất Excel</a>
    </div>

    @if($documents->count() > 0)
        <table>
            <thead>
                @if ($isPartyOrganization)
                    <tr>
                        <th>Số TT</th>
                        <th>Số của văn bản</th>
                        <th>Ký hiệu của văn bản</th>
                        <th>Ngày, tháng, năm văn bản</th>
                        <th>Tên cơ quan, tổ chức ban hành văn bản</th>
                        <th>Tên loại văn bản</th>
                        <th>Trích yếu nội dung</th>
                        <th>Người ký</th>
                        <th>Độ mật</th>
                        <th>Loại bản</th>
                        <th>Trang số</th>
                        <th>Số trang</th>
                        <th>Số lượng tệp (file)</th>
                        <th>Tên tệp</th>
                        <th>Thời gian tài liệu</th>
                        <th>Chế độ sử dụng</th>
                        <th>Từ khóa</th>
                        <th>Ghi chú</th>
                        <th>Ngôn ngữ</th>
                        <th>Bút tích</th>
                        <th>Chuyên đề</th>
                        <th>Ký hiệu thông tin</th>
                        <th>Mức độ tin cậy</th>
                        <th>Tình trạng vật lý</th>
                    </tr>
                @else
                    <tr>
                        <th>Số thứ tự</th>
                        <th>Số, Ký hiệu</th>
                        <th>Ngày tháng văn bản</th>
                        <th>Trích yếu nội dung văn bản</th>
                        <th>Tác giả văn bản</th>
                        <th>Tờ số</th>
                        <th>Ghi chú</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @foreach($documents as $document)
                    @if ($isPartyOrganization)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $document->document_number ?? $document->document_code ?? '' }}</td>
                            <td>{{ $document->document_symbol ?? $document->document_code ?? '' }}</td>
                            <td>{{ $document->document_date ? \Carbon\Carbon::parse($document->document_date)->format('d/m/Y') : '' }}</td>
                            <td>{{ $document->issuing_agency ?? '' }}</td>
                            <td>{{ $document->docType->name ?? '' }}</td>
                            <td>{{ $document->description ?? '' }}</td>
                            <td>{{ $document->signer ?? $document->author ?? '' }}</td>
                            <td>{{ $document->security_level ?? '' }}</td>
                            <td>{{ $document->copy_type ?? '' }}</td>
                            <td>{{ $document->page_number ?? '' }}</td>
                            <td>{{ $document->total_pages ?? '' }}</td>
                            <td>{{ $document->file_count ?? '' }}</td>
                            <td>{{ $document->file_name ?? '' }}</td>
                            <td>{{ $document->document_duration ?? '' }}</td>
                            <td>{{ $document->usage_mode ?? '' }}</td>
                            <td>{{ $document->keywords ?? '' }}</td>
                            <td>{{ $document->note ?? '' }}</td>
                            <td>{{ $document->language ?? '' }}</td>
                            <td>{{ $document->handwritten ?? '' }}</td>
                            <td>{{ $document->topic ?? '' }}</td>
                            <td>{{ $document->information_code ?? '' }}</td>
                            <td>{{ $document->reliability_level ?? '' }}</td>
                            <td>{{ $document->physical_condition ?? '' }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $document->document_code ?? '' }}</td>
                            <td>{{ $document->document_date ? \Carbon\Carbon::parse($document->document_date)->format('d/m/Y') : '' }}</td>
                            <td>{{ $document->description ?? '' }}</td>
                            <td>{{ $document->author ?? $document->signer ?? '' }}</td>
                            <td>{{ $document->page_number ?? '' }}</td>
                            <td>{{ $document->note ?? '' }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <div>
            Tổng số tài liệu: {{ $documents->count() }}
        </div>
    @else
        <div style="text-align: center; padding: 20px; color: #666;">
            <p>Không có tài liệu nào trong hồ sơ này.</p>
        </div>
    @endif
</body>
</html>
