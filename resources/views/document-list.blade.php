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
    <h3 style="text-align: center">MỤC LỤC VĂN BẢN, TÀI LIỆU</h3>
    <b><p style="text-align: center; margin-bottom: 40px;">{{ $archiveRecord->title }}</p></b>
    <p style="text-align: center; margin-bottom: 40px;">Hộp số: {{$archiveRecord->box->code}}</p>
    
    <div style="display: flex; justify-content: center; gap: 20px; margin: 20px auto;" class="no-print">
        <button onclick="window.print()" class="export-button" style="border: none; cursor: pointer;">In mục lục tài liệu</button>
        <a href="{{ route('archive-records.documents.export-excel', $archiveRecord->id) }}" class="export-button" style="background-color: #28a745;" target="_blank">Xuất Excel</a>
    </div>

    @if($documents->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Số thứ tự</th>
                    <th>Số, Ký hiệu</th>
                    <th>Ngày tháng văn bản</th>
                    <th>Trích yếu nội dung văn bản</th>
                    <th>Tác giả văn bản</th>
                    <th>Tờ số</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $document)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $document->document_code ?? 'N/A' }}</td>
                        <td>{{ $document->document_date ?? 'N/A' }}</td>
                        <td>{{ $document->description }}</td>
                        <td>{{ $document->author ?? 'N/A' }}</td>
                        <td>{{ $document->page_number ?? 'N/A' }}</td>
                        <td>{{ $document->note ?? 'N/A' }}</td>
                    </tr>
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
