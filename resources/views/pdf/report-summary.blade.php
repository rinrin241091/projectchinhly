<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18mm 15mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
        }
        .header {
            text-align: center;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 14px;
            text-transform: uppercase;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .header .subtitle {
            font-size: 10px;
            color: #555;
        }
        .meta {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #888;
            padding: 6px 5px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
        }
        td {
            border: 1px solid #aaa;
            padding: 5px 5px;
            font-size: 10px;
        }
        td.num {
            text-align: right;
        }
        td.center {
            text-align: center;
        }
        tfoot td {
            font-weight: bold;
            background-color: #fffde7;
            border-top: 2px solid #888;
        }
        .empty-row td {
            text-align: center;
            padding: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Báo cáo tổng hợp khối lượng chỉnh lý hồ sơ</h1>
        <div class="subtitle">
            @if ($dateFrom || $dateTo)
                Giai đoạn:
                @if ($dateFrom) Từ {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} @endif
                @if ($dateTo) đến {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }} @endif
            @else
                Tổng hợp toàn bộ thời gian
            @endif
        </div>
    </div>

    <div class="meta">Ngày lập báo cáo: {{ now()->format('d/m/Y') }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 28px;">STT</th>
                <th style="text-align: left;">Tên phông lưu trữ</th>
                <th>Số hồ sơ chỉnh lý</th>
                <th>Số văn bản / tài liệu</th>
                <th>Số hộp</th>
                <th>Tổng số trang</th>
                <th>Mét giá (m)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="center">{{ $row['stt'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="num">{{ number_format($row['records_count'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['documents_count'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['boxes_count'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['total_pages'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['met_gia'], 3, ',', '.') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="7">Không có dữ liệu phù hợp</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;">TỔNG CỘNG</td>
                <td class="num">{{ number_format($totalRecords, 0, ',', '.') }}</td>
                <td class="num">{{ number_format($totalDocuments, 0, ',', '.') }}</td>
                <td class="num">{{ number_format($totalBoxes, 0, ',', '.') }}</td>
                <td class="num">{{ number_format($totalPages, 0, ',', '.') }}</td>
                <td class="num">{{ number_format($totalMetGia, 3, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
