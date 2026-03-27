<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreatePerformanceIndexesSeeder extends Seeder
{
    /**
     * Run the database seeds to create performance indexes
     * 
     * Run with: php artisan db:seed --class=CreatePerformanceIndexesSeeder
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily to avoid issues
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Archive Records Indexes
            $this->createIndexIfNotExists('archive_records', 'idx_archive_records_org_date', [
                'organization_id', 'created_at'
            ], 'Organizations and dates filtering');

            $this->createIndexIfNotExists('archive_records', 'idx_archive_records_box_id', [
                'box_id'
            ], 'Box lookups');

            // Documents Indexes
            $this->createIndexIfNotExists('documents', 'idx_documents_archive_id', [
                'archive_record_id'
            ], 'Archive record document lookups');

            $this->createIndexIfNotExists('documents', 'idx_documents_archive_id_created', [
                'archive_record_id', 'created_at'
            ], 'Archive record with date filtering');

            // Archive Record Items Indexes
            $this->createIndexIfNotExists('archive_record_items', 'idx_archive_record_items_org', [
                'organization_id'
            ], 'Organization filtering');

            $this->createIndexIfNotExists('archive_record_items', 'idx_archive_record_items_search', [
                'archive_record_item_code', 'title'
            ], 'Search functionality');

            // Activities Indexes
            $this->createIndexIfNotExists('activities', 'idx_activities_causer_created', [
                'causer_id', 'created_at'
            ], 'User activity tracking');

            $this->createIndexIfNotExists('activities', 'idx_activities_subject_type', [
                'subject_type'
            ], 'Activity type filtering');

            // Organizations Indexes
            $this->createIndexIfNotExists('organizations', 'idx_organizations_archival_id', [
                'archival_id', 'name'
            ], 'Archival organization lookups');

            // Boxes Indexes
            $this->createIndexIfNotExists('boxes', 'idx_boxes_shelf_id', [
                'shelf_id'
            ], 'Shelf box lookups');

            // Shelves Indexes  
            $this->createIndexIfNotExists('shelves', 'idx_shelves_storage_id', [
                'storage_id'
            ], 'Storage shelf lookups');

            // Users Indexes
            $this->createIndexIfNotExists('users', 'idx_users_role_created', [
                'role', 'created_at'
            ], 'User role filtering');

            // Storages Indexes
            $this->createIndexIfNotExists('storages', 'idx_storages_archival_id', [
                'archival_id'
            ], 'Archival storage lookups');

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->command->info('✅ All performance indexes created successfully!');

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->command->error('❌ Error creating indexes: ' . $e->getMessage());
        }
    }

    /**
     * Create index if it doesn't exist
     */
    private function createIndexIfNotExists(string $table, string $indexName, array $columns, string $description): void
    {
        // Check if table exists first
        $tableExists = DB::select("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [
            config('database.connections.mysql.database'),
            $table
        ]);

        if (empty($tableExists)) {
            $this->command->info("→ Table '{$table}' does not exist, skipping index '{$indexName}'");
            return;
        }

        $columnList = implode(', ', $columns);
        
        // Check if index exists
        $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$indexName]);
        
        if (empty($indexes)) {
            $columnString = implode('`,`', $columns);
            $sql = "CREATE INDEX {$indexName} ON {$table} (`{$columnString}`)";
            
            DB::statement($sql);
            $this->command->info("✓ Index '{$indexName}' created on {$table}({$columnList}) - {$description}");
        } else {
            $this->command->info("→ Index '{$indexName}' already exists on {$table}");
        }
    }
}
