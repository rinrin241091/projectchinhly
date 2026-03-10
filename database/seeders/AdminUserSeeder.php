<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a sample archival
        $archival = \App\Models\Archival::firstOrCreate(
            ['code' => 'ARCHIVAL_001'],
            [
                'name' => 'Cơ quan lưu trữ mẫu',
                'address' => 'Địa chỉ mẫu',
            ]
        );

        // Create sample organizations if they don't exist
        $orgA = Organization::firstOrCreate(
            ['code' => 'PHONG_A'],
            [
                'name' => 'Phòng A',
                'archival_id' => $archival->id,
                'archivals_time' => '2020-2025',
            ]
        );
        $orgB = Organization::firstOrCreate(
            ['code' => 'PHONG_B'],
            [
                'name' => 'Phòng B',
                'archival_id' => $archival->id,
                'archivals_time' => '2015-2020',
            ]
        );
        $orgC = Organization::firstOrCreate(
            ['code' => 'PHONG_C'],
            [
                'name' => 'Phòng C',
                'archival_id' => $archival->id,
                'archivals_time' => '2010-2015',
            ]
        );

        // Tạo hoặc cập nhật admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );
        // Gán admin vào tất cả phòng với role admin
        $admin->organizations()->syncWithoutDetaching([
            $orgA->id => ['role' => 'admin'],
            $orgB->id => ['role' => 'admin'],
            $orgC->id => ['role' => 'admin'],
        ]);

        // Tạo hoặc cập nhật user thường
        $user = User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User',
                'password' => Hash::make('user123'),
                'role' => 'user',
            ]
        );
        // Gán user vào các phòng với các vai trò khác nhau để demo layout
        $user->organizations()->syncWithoutDetaching([
            $orgA->id => ['role' => 'editor'],
            $orgB->id => ['role' => 'viewer'],
        ]);

        // Tạo user bổ sung với quyền khác nhau trên các phòng
        $user2 = User::updateOrCreate(
            ['email' => 'tudangm10@gmail.com'],
            [
                'name' => 'Tú Dăng',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
        $user2->organizations()->syncWithoutDetaching([
            $orgB->id => ['role' => 'viewer'],
            $orgC->id => ['role' => 'editor'],
        ]);
    }
}
