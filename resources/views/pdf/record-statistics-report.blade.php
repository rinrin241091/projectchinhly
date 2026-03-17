<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .header { text-align: center; margin-bottom: 16px; }
        .header h1 { font-size: 14px; text-transform: uppercase; font-weight: bold; margin: 0 0 4px 0; }
        .header .subtitle { font-size: 10px; color: #555; }
        .meta { text-align: right; font-size: 10px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f0f0f0; border: 1px solid #888; padding: 6px 5px; text-align: center; font-size: 10px; font-weight: bold; }
        td { border: 1px solid #aaa; padding: 5px 5px; font-size: 10px; }
        td.num { text-align: right; }
        tfoot td { font-weight: bold; background-color: #fffde7; border-top: 2px solid #888; }
        .empty-row td { text-align: center; padding: 14px; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bao cao thong ke ho so</h1>
        <div class="subtitle">Che do: {{ $modeLabel }}</div>
    </div>

    <div class="meta">Ngay lap bao cao: {{ now()->format('d/m/Y') }}</div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Tieu chi</th>
                <th>So ho so</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ number_format($row['records_count'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="2">Khong co du lieu phu hop</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td style="text-align: right;">TONG CONG</td>
                <td class="num">{{ number_format($totalRecords, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
