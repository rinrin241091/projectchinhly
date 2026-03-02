<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .label {
            border: 1px solid black;
            width: 250px;
            height: 160px;
            padding: 10px;
            float: left;
            margin: 5px;
        }
        .label h3 {
            text-align: center;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    @foreach ($boxes as $box)
        <div class="label">
            <div style="text-align: center;">
                <strong>UBND HUYỆN ...<br>PHÒNG NỘI VỤ</strong>
            </div>
            <h3>HỘP SỐ {{ $box->code }}</h3>
            <div>
                Từ hồ sơ số: {{ $box->start_record ?? '...' }}<br>
                Đến hồ sơ số: {{ $box->end_record ?? '...' }}<br>
                Năm: {{ $box->year ?? '...' }}<br>
                THĐG: {{ $box->retention_period ?? '...' }}
            </div>
        </div>
    @endforeach
</body>
</html>