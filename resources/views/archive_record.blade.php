<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết mục lục hồ sơ</title>
    <style>
        /* Thiết lập margin cho trang in bằng 0 */
        @page {
            margin: 0;
        }
        .container {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background-color: #f8f9fa;
        }
        .cover-page {
            text-align: center;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #333;
            border-radius: 6px;
            width: 90%;
            margin-bottom: 40px;
        }
        .cover-page h1, .cover-page h2 {
            margin: 5px 0;
        }
        .cover-title {
            font-size: 36pt;
            font-weight: bold;
            margin: 20px 0;
        }
        .cover-info {
            font-size: 14pt;
            margin: 8px 0;
            color: #555;
        }
        /* Class để căn trái */
        .cover-info-left {
            text-align: left;
        }
        /* Class để căn phải */
        .cover-info-right {
            text-align: right;
        }
        .records-list {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #333;
            border-radius: 6px;
        }
        .records-list h2 {
            font-size: 20pt;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 2px solid #333;
        }
        table, th, td {
            border: 1px solid #333;
        }
        /* Để thead tự động lặp lại khi in (nhiều trình duyệt hỗ trợ) */
        thead {
            display: table-header-group;
        }
        th {
            background-color: #e9ecef;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            text-align: left;
        }
        .total-records {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #333;
            background-color: #e9ecef;
            border-radius: 4px;
        }
        .no-records {
            text-align: center;
            font-size: 14pt;
            color: #666;
            padding: 20px;
            border: 1px solid #333;
            border-radius: 4px;
        }
        /* Ẩn các thành phần không cần in */
        .no-print {
            display: block;
        }
        @media print {
            .no-print {
                display: none;
            }
            .page-break {
                display: block;
                page-break-after: always;
            }
            /* Để cover-page chiếm toàn bộ trang in đầu tiên và bố trí nội dung theo flex */
            .cover-page {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                width: 95vw;
                height: 95vh; /* Điều chỉnh cho vừa trang in */
                padding: 40px;
                margin: 0;
                box-sizing: border-box;
                border: 2px solid #333;
                border-radius: 0;
            }
            .center-section {
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .bottom-section {
                text-align: center;
            }
            /* Ẩn border của danh sách hồ sơ từ trang 2 trở đi */
            .records-list {
                border: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Bìa mục lục (trang 1 in) với bố trí theo flex -->
        <div class="cover-page">
            <!-- Phần đầu: Tên Cơ Quan Lưu Trữ -->
            <div class="top-section">
                <h2 class="cover-info">{{ $archiveRecordItem->organization->archival->name ?? 'Tên Cơ Quan Lưu Trữ' }}</h2>
            </div>
            
            <!-- Phần giữa: "MỤC LỤC HỒ SƠ", tên phông, số trang, ... -->
            <div class="center-section">
                <div class="cover-title">MỤC LỤC HỒ SƠ</div>
                <p class="cover-info"><strong>PHÔNG LƯU TRỮ:</strong> {{ $archiveRecordItem->organization->name ?? 'N/A' }}</p>
                <p class="cover-info">
                    Từ hộp số {{ $fromBox }} đến hộp số {{ $toBox }} 
                    (Từ hồ sơ số {{ $fromRecord }} đến hồ sơ số {{ $toRecord }})
                </p>
                <p class="cover-info cover-info-left"><strong>Phông số:</strong> {{ $archiveRecordItem->organization->code ?? 'N/A' }}</p>
                <p class="cover-info cover-info-left"><strong>Mục lục số:</strong> {{ $archiveRecordItem->archive_record_item_code ?? 'N/A' }}</p>
                <p class="cover-info cover-info-left"><strong>Số trang:</strong> {{ $archiveRecordItem->page_num ?? $pageCount }}</p>
                <p class="cover-info cover-info-right"><strong>Thời hạn bảo quản:</strong> {{ $archiveRecordItem->description }}</p>
                
            </div>
            
            <!-- Phần dưới: Năm tài liệu, canh sát lề dưới -->
            <div class="bottom-section">
                <p class="cover-info"><strong>Năm:</strong> {{ $archiveRecordItem->document_date }}</p>
            </div>
        </div>

        <!-- Form nhập số trang thực tế và nút in (sẽ ẩn khi in) -->
        <div class="no-print" style="margin-bottom: 20px; text-align: center;">
            <form method="POST" action="{{ route('archive-record-items.update-page-num', ['id' => $archiveRecordItem->id]) }}" style="display: inline-block; margin-right: 20px;">
                @csrf
                <label for="page_num" style="font-size:12pt;">Nhập số trang thực tế:</label>
                <input type="number" name="page_num" id="page_num" value="{{ $archiveRecordItem->page_num ?? '' }}" style="width: 50px; font-size:12pt;" />
                <button type="submit" style="font-size:12pt;">Lưu</button>
            </form>
            
            <!-- Nút in mục lục -->
            <button onclick="window.print()" style="font-size:12pt; padding: 5px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                In mục lục
            </button>
        </div>

        <!-- Ngắt trang in sau cover -->
        <div class="page-break"></div>

        <!-- Danh sách hồ sơ (trang 2 trở đi) -->
        <div class="records-list">
            <h2>Danh sách hồ sơ</h2>
            @if($records->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Hộp số</th>
                            <th>Hồ sơ số</th>
                            <th>Tiêu đề</th>
                            <th>Ngày bắt đầu / Ngày kết thúc</th>
                            <th>Thời hạn bảo quản</th>
                            @if($archiveRecordItem->description == 'Có thời hạn')
                                <th>Tình trạng tài liệu</th>
                            @endif
                            <th>Số lượng tờ</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                            <tr>
                                <td>{{ $record->box->code ?? 'N/A' }}</td>
                                <td>{{ $record->code ?? 'N/A' }}</td>
                                <td>{{ $record->title ?? 'N/A' }}</td>
                                <td>
                                    {{ $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('d/m/Y') : 'N/A' }}<br>
                                    {{ $record->end_date ? \Carbon\Carbon::parse($record->end_date)->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td>{{ $record->preservation_duration ?? 'N/A' }}</td>
                                @if($archiveRecordItem->description == 'Có thời hạn')
                                    <td>{{ $record->condition ?? 'N/A' }}</td>
                                @endif
                                <td>{{ $record->page_count ?? 'N/A' }}</td>
                                <td>{{$record->note}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Nội dung TỜ KẾT THÚC -->
                <div style="text-align: center; margin-top: 20px; font-size: 14pt; font-weight: bold;">
                    TỜ KẾT THÚC
                </div>

                <div style="margin-top: 10px; font-size: 12pt; text-align: left;">
                    <p>Mục lục này gồm có: <strong>{{ $records->count() }} hồ sơ (HS), {{ $boxCount }} cặp (hộp)</strong></p>
                    <p>Viết bằng chữ: <strong>{{ $recordCountInWords }} (Hồ sơ), {{ $boxCountInWords }} (hộp)</strong></p>
                    <p>Từ hồ sơ số <strong>{{ $fromRecord }}</strong> đến hồ sơ số <strong>{{ $toRecord }}</strong>, từ cặp số <strong>{{ $fromBox }}</strong> đến cặp số <strong>{{ $toBox }}</strong></p>
                    <p>Số chèn, số đúp: <strong>0</strong></p>
                    <p>Phần kê hồ sơ, tài liệu của mục lục này gồm có <strong>{{ $pageCount }}</strong> tờ (Đánh số liên tục từ trang 01 đến trang {{ $pageCount }})</p>
                </div>

               
            @else
                <div class="no-records">
                    Không có hồ sơ nào trong mục lục.
                </div>
            @endif
        </div>
    </div>
</body>
</html>
