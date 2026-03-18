<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR tài liệu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
            margin: 0;
            padding: 24px;
        }
        .card {
            max-width: 720px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            padding: 28px;
            text-align: center;
        }
        .card h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }
        .card p {
            margin: 0 0 20px;
            color: #6b7280;
        }
        .qr-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            border: 1px solid #d1d5db;
            border-radius: 16px;
            background: #fff;
        }
        .payload {
            margin-top: 24px;
            text-align: left;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            white-space: pre-line;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>QR tài liệu</h1>
        <p>Mở màn hình này để quét QR lớn, ổn định hơn so với QR nhỏ trong bảng.</p>

        <div class="qr-box">
            <img src="{{ $qrUrl }}" alt="QR tài liệu" style="width: 320px; height: 320px;">
        </div>

        <div class="payload">{{ $qrText }}</div>
    </div>
</body>
</html>
