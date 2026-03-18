<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài liệu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            color: #1f2937;
            margin: 0;
            padding: 24px;
        }
        .card {
            max-width: 720px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0b74c7, #0f5ea6);
            color: #ffffff;
            padding: 24px;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
        }
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
        }
        .row {
            padding: 14px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .row:last-child {
            border-bottom: 0;
        }
        .label {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .value {
            font-size: 18px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Thông tin tài liệu</h1>
            <p>Thông tin hiển thị khi quét QR tài liệu.</p>
        </div>

        <div class="content">
            <div class="row">
                <span class="label">Tên tài liệu</span>
                <span class="value">{{ $documentName }}</span>
            </div>
            <div class="row">
                <span class="label">Tên hồ sơ</span>
                <span class="value">{{ $recordName }}</span>
            </div>
            <div class="row">
                <span class="label">Tên hộp</span>
                <span class="value">{{ $boxName }}</span>
            </div>
            <div class="row">
                <span class="label">Tên kệ</span>
                <span class="value">{{ $shelfName }}</span>
            </div>
        </div>
    </div>
</body>
</html>
