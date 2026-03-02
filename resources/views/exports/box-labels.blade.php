<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .label {
            border: 1px solid #000;
            width: 250px;
            height: 150px;
            padding: 10px;
            margin: 10px;
            display: inline-block;
            page-break-inside: avoid;
        }
        .center { text-align: center; font-size: 16px; font-weight: bold; }
        .info { font-size: 14px; }
    </style>
</head>
<body>
@foreach ($boxes as $box)
    <div class="label">
        <div class="center">UBND HUYỆN THANH TRÌ<br>PHÒNG NỘI VỤ</div>
        <div class="center">HỘP SỐ<br><span style="font-size: 28px;">{{ $box->code }}</span></div>
        <div class="info">
            {{-- Từ hồ sơ số: {{ $box->records->min('code') ?? '___' }}<br>
            Đến hồ sơ số: {{ $box->records->max('code') ?? '___' }}<br>
            Năm: {{ $box->year ?? '____' }}<br> --}}
            THĐG: Có thời hạn
        </div>
    </div>
@endforeach
</body>
</html>