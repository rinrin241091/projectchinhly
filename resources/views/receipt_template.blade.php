<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu Tin</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h1, h2 { text-align: center;  font-size: 15pt;}
        #printable { margin: 20px; border: 1px solid #000; padding: 20px; }
    </style>
    
</head>
<body>
    <div id="printable">
        <h1><b>PHIẾU TIN</b></h1> </br>
        <p><b>1. Tên (hoặc mã) kho lưu trữ:</b> {{ $record->storage->name }}</p>
        <p><b>2. Tên (hoặc mã) phông:</b> {{ $record->organization->name }}</p>
        <p><b>3. Số lưu trữ:</b> </br>a. Mục lục số: {{ $record->archiveRecordItem->title }}</p>
        <p>   b. Hộp số: {{ $record->box->description }}</p>
        <p>   c. Hồ sơ số: {{ $record->reference_code }}</p>
        <p><b>4. Ký hiệu thông tin:</b> {{ $record->symbols_code }}</p>
        <p><b>5. Tiêu đề hồ sơ:</b> {{ $record->title }}</p>
        <p><b>6. Chú giải: </b>{{ $record->description }}</p>
        <p><b>7. Thời gian của tài liệu:</b></br>a. Ngày bắt đầu: {{ $record->start_date }}</p>
        <p>   b. Ngày kết thúc: {{ $record->end_date }}</p>
        <p><b>8. Ngôn ngữ:</b> {{ $record->language }}</p>
        <p><b>9. Số lượng tài liệu:</b> {{ $record->page_count }}</p>
        <p><b>10. Bút tích:</b> {{ $record->handwritten}}</p>
        <p><b>11. Thời hạn bảo quản:</b> {{ $record->preservation_duration }}</p>
        <p><b>12. Chế độ sử dụng:</b> {{ $record->usage_mode }}</p>
        <p><b>13. Tình trạng vật lý:</b> {{ $record->condition }}</p>
        <p><b>14. Ghi chú:</b> {{ $record->note }}</p>

    </div>
    <div>
        <button onclick="triggerPrintDoc()" style="margin-top: 10px;">In ngay</button>
    </div>
</body>
</html>
