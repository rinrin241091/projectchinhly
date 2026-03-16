<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MegaArchiveSeeder extends Seeder
{
    public function run()
    {
        // Ensure archival masters exist for FK constraints.
        $archivalIds = DB::table('archivals')->pluck('id')->all();

        if (count($archivalIds) < 2) {
            if (! DB::table('archivals')->where('identifier', 'ARCHIVAL_MEGA_001')->exists()) {
                DB::table('archivals')->insert([
                    'identifier' => 'ARCHIVAL_MEGA_001',
                    'name' => 'Cơ quan lưu trữ Mega 1',
                    'address' => 'Đà Nẵng',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (! DB::table('archivals')->where('identifier', 'ARCHIVAL_MEGA_002')->exists()) {
                DB::table('archivals')->insert([
                    'identifier' => 'ARCHIVAL_MEGA_002',
                    'name' => 'Cơ quan lưu trữ Mega 2',
                    'address' => 'Đà Nẵng',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $archivalIds = DB::table('archivals')->pluck('id')->all();
        }

        $primaryArchivalId = $archivalIds[0];

        // Ensure document types exist for FK constraints.
        if (! DB::table('doc_types')->exists()) {
            DB::table('doc_types')->insert([
                [
                    'name' => 'Công văn',
                    'description' => 'Văn bản công văn',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Quyết định',
                    'description' => 'Văn bản quyết định',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Báo cáo',
                    'description' => 'Văn bản báo cáo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        $docTypeIds = DB::table('doc_types')->pluck('id')->all();

        // =========================
        // ORGANIZATION (PHÔNG)
        // =========================

        $dang = DB::table('organizations')->insertGetId([
            'code' => 'H17.DANG',
            'archival_id' => $primaryArchivalId,
            'name' => 'Phông Đảng Thành phố Đà Nẵng',
            'type' => 'Đảng',
            'archivals_time' => '1990-2025',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $chinhquyen = DB::table('organizations')->insertGetId([
            'code' => 'H17.28.08.01',
            'archival_id' => $primaryArchivalId,
            'name' => 'Phông UBND Thành phố Đà Nẵng',
            'type' => 'Chính quyền',
            'archivals_time' => '2000-2025',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // =========================
        // KHO
        // =========================

        $storages = [];

        for ($i=1;$i<=10;$i++)
        {
            $storages[] = DB::table('storages')->insertGetId([
                'code' => 'K'.str_pad($i,2,'0',STR_PAD_LEFT),
                'name' => 'Kho lưu trữ '.$i,
                'location' => 'Tầng '.rand(1,5),
                'archival_id' => $archivalIds[array_rand($archivalIds)],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // =========================
        // KỆ
        // =========================

        $shelves=[];

        for ($i=1;$i<=120;$i++)
        {
            $shelves[] = DB::table('shelves')->insertGetId([
                'storage_id' => $storages[array_rand($storages)],
                'code' => 'KE'.str_pad($i,4,'0',STR_PAD_LEFT),
                'description' => 'Kệ lưu trữ '.$i,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // =========================
        // HỘP
        // =========================

        $boxes=[];

        for ($i=1;$i<=1200;$i++)
        {
            $boxes[] = DB::table('boxes')->insertGetId([
                'shelf_id' => $shelves[array_rand($shelves)],
                'code' => 'HOP'.str_pad($i,5,'0',STR_PAD_LEFT),
                'description' => 'Hộp hồ sơ '.$i,
                'type' => 'Hồ sơ giấy',
                'record_count' => rand(20,80),
                'page_count' => rand(200,1200),
                'location' => 'Ngăn '.rand(1,20),
                'status' => 'Đang lưu trữ',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // =========================
        // MỤC LỤC
        // =========================

        $items=[];

        for ($i=1;$i<=20;$i++)
        {
            $items[] = DB::table('archive_record_items')->insertGetId([
                'archive_record_item_code' => 'ML'.str_pad($i,3,'0',STR_PAD_LEFT),
                'organization_id' => rand(0,1) ? $dang : $chinhquyen,
                'title' => 'Mục lục hồ sơ '.$i,
                'page_num' => rand(1,20),
                'document_date' => '2010-2025',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // =========================
        // HỒ SƠ (10000)
        // =========================

        for ($i=1;$i<=10000;$i++)
        {

            $org = rand(0,1) ? $dang : $chinhquyen;

            $code = str_pad($i,4,'0',STR_PAD_LEFT);

            $reference = 'H17.28.08.01-2024-'.$code;

            $record = DB::table('archive_records')->insertGetId([

                'reference_code' => $reference,
                'code' => $code,
                'organization_id' => $org,
                'box_id' => $boxes[array_rand($boxes)],
                'storage_id' => $storages[array_rand($storages)],
                'archive_record_item_id' => $items[array_rand($items)],
                'symbols_code' => rand(1000,9999),

                'title' => 'Hồ sơ quản lý xây dựng số '.$i,

                'description' =>
                'Hồ sơ cấp phép xây dựng và quản lý đô thị năm 2024.',

                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',

                'language' => 'Tiếng Việt',
                'handwritten' => 'Không',
                'usage_mode' => 'Công khai',

                'preservation_duration' => '50 năm',

                'page_count' => rand(50,400),

                'condition' => 'Tốt',

                'created_at' => now(),
                'updated_at' => now()

            ]);

            // =========================
            // VĂN BẢN
            // =========================

            $docCount = rand(2,5);

            for ($d=1;$d<=$docCount;$d++)
            {

                DB::table('documents')->insert([

                    'archive_record_id' => $record,

                    'doc_type_id' => $docTypeIds[array_rand($docTypeIds)],

                    'document_code' =>
                        rand(1000,9999).'/UBND-'.rand(100,999),

                    'description' =>
                        'Văn bản xử lý hồ sơ xây dựng',

                    'author' =>
                        'UBND Thành phố Đà Nẵng',

                    'page_number' =>
                        rand(1,30),

                    'document_date' =>
                        '2024-'.rand(1,12).'-'.rand(1,28),

                    'created_at' => now(),
                    'updated_at' => now()

                ]);

            }

        }

    }
}