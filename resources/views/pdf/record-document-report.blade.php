<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 14mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { font-size: 13px; text-transform: uppercase; font-weight: bold; margin: 0 0 4px 0; }
        .meta { text-align: right; font-size: 9px; color: #666; margin-bottom: 10px; }
        .summary { margin-bottom: 8px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f0f0f0; border: 1px solid #888; padding: 5px 4px; text-align: center; font-size: 9px; font-weight: bold; }
        td { border: 1px solid #aaa; padding: 4px 4px; font-size: 9px; }
        td.num { text-align: right; }
        tfoot td { font-weight: bold; background-color: #fffde7; border-top: 2px solid #888; }
        .empty-row td { text-align: center; padding: 12px; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bao cao tai lieu trong ho so</h1>
    </div>

    <div class="meta">Ngay lap bao cao: {{ now()->format('d/m/Y') }}</div>

    <div class="summary">
        Tong so ho so: <strong>{{ number_format($totalRecords, 0, ',', '.') }}</strong> |
        Tong so tai lieu: <strong>{{ number_format($totalDocuments, 0, ',', '.') }}</strong> |
        Tong so trang: <strong>{{ number_format($totalPages, 0, ',', '.') }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Ho so</th>
                <th style="text-align: left;">Tieu de</th>
                <th>Tai lieu</th>
                <th>Trang</th>
                <th>Kiem tra</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['record_label'] }}</td>
                    <td>{{ $row['record_title'] }}</td>
                    <td class="num">{{ number_format($row['documents_count'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['document_pages'], 0, ',', '.') }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="5">Khong co du lieu ho so</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;">TONG CONG</td>
                <td class="num">{{ number_format($totalDocuments, 0, ',', '.') }}</td>
                <td class="num">{{ number_format($totalPages, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
