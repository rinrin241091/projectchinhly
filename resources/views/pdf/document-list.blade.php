<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách tài liệu - Hồ sơ {{ $archiveRecord->title }}</title>
    <style>
        @font-face {
            font-family: 'Arial Unicode MS';
            src: local('Arial Unicode MS');
        }
        
        body {
            font-family: 'Arial Unicode MS', Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>Danh sách tài liệu - Hồ sơ1: {{ $archiveRecord->title }}</h1>
    
    @if($documents->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Số, Ký hiệu</th>
                    <th>Ngày tháng văn bản</th>
                    <th>Trích yếu nội dung văn bản</th>
                    <th>Tác giả văn bản</th>
                    <th>Tổ số</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $document)
                    <tr>
                        <td>{{ $document->document_code ?? '' }}</td>
                        <td>{{ $document->document_date ?? '' }}</td>
                        <td>{{ $document->description }}</td>
                        <td>{{ $document->author ?? '' }}</td>
                        <td>{{ $document->organization ?? '' }}</td>
                        <td>{{ $document->note ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div>
            Tổng số tài liệu: {{ $documents->count() }}
        </div>
    @else
        <div style="text-align: center; padding: 20px;">
            <p>Không có tài liệu nào trong hồ sơ này.</p>
        </div>
    @endif
    
    <div class="footer">
        Ngày xuất: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
