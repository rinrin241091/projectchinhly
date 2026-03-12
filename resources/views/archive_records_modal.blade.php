
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bìa Mục Lục Hồ Sơ</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 2cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14pt;
            margin: 0;
            padding: 0;
        }

        .a4-border {
            width: 100%;
            height: 100%;
            border: 3px solid #000;
            padding: 40px;
            box-sizing: border-box;
        }

        .center {
            text-align: center;
        }

        .big-title {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .sub-title {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .info {
            margin-bottom: 10px;
            font-size: 14pt;
        }

        .two-columns {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 14pt;
        }

        .footer {
            text-align: right;
            margin-top: 40px;
            font-size: 14pt;
        }
        /* Cho modal full width */
        .modal-fullscreen {
            width: 100vw;        /* Chiều ngang toàn màn hình */
            max-width: 100vw;    /* Không giới hạn chiều ngang */
            margin: 0 auto;
        }

        /* Bảng scroll ngang nếu quá rộng */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="a4-border">
        <div class="center">
            <div class="big-title">{{ $archival_name ?? 'ỦY BAN NHÂN DÂN QUẬN THANH KHÊ' }}</div>

            <div class="sub-title">MỤC LỤC HỒ SƠ</div>

            <div class="info">
                {{ $organization_name ?? 'PHÔNG LƯU TRỮ: PHÒNG QUẢN LÝ ĐÔ THỊ QUẬN THANH KHÊ' }}
            </div>

            <div class="info">
                Từ Hộp số {{ $from_box ?? '168' }} đến cặp số {{ $to_box ?? '…' }} (Từ hồ sơ số {{ $from_record ?? '503' }} đến hồ sơ số {{ $to_record ?? '…' }})
            </div>

            <div class="two-columns">
                <div>Phông số: {{ $phong_so ?? '01' }}</div>
                <div>Thời hạn bảo quản: {{ $bao_quan ?? 'Có thời hạn' }}</div>
            </div>

            <div class="two-columns">
                <div>Mục lục số: {{ $muc_luc_so ?? '02' }}</div>
                <div>Số trang: {{ $so_trang ?? '15' }}</div>
            </div>

            <div class="footer">
                Đà Nẵng, năm {{ $nam ?? '2023' }}
            </div>
        </div>
    </div>
</body>
</html>










<div class="p-6">
    
    @if($records->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b text-left">Mã hồ sơ</th>
                        <th class="px-4 py-2 border-b text-left">Tiêu đề</th>
                        <th class="px-4 py-2 border-b text-left">Ngày bắt đầu</th>
                        <th class="px-4 py-2 border-b text-left">Ngày kết thúc</th>
                        <th class="px-4 py-2 border-b text-left">Tình trạng</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b">{{ $record->code ?? '' }}</td>
                            <td class="px-4 py-2 border-b">{{ $record->title }}</td>
                            <td class="px-4 py-2 border-b">{{ $record->start_date }}</td>
                            <td class="px-4 py-2 border-b">{{ $record->end_date }}</td>
                            <td class="px-4 py-2 border-b">{{ $record->condition }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 text-sm text-gray-600">
            Tổng số: {{ $records->count() }} hồ sơ
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <p>Không có hồ sơ nào trong mục lục này.</p>
        </div>
    @endif
    
   
