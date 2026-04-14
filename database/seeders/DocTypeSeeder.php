<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocType;

class DocTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default document types if they don't exist
        $defaultDocTypes = [
            [
                'name' => 'Văn bản thường',
                'description' => 'Loại tài liệu mặc định'
            ],
            [
                'name' => 'Văn bản urgency',
                'description' => 'Tài liệu khẩn cấp'
            ],
            [
                'name' => 'Báo cáo',
                'description' => 'Tài liệu báo cáo'
            ],
            [
                'name' => 'Biên bản họp',
                'description' => 'Biên bản cuộc họp'
            ],
        ];

        foreach ($defaultDocTypes as $docType) {
            DocType::firstOrCreate(
                ['name' => $docType['name']],
                $docType
            );
        }
    }
}
