<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateAdditionalPerformanceIndexesSeeder extends Seeder
{
    /**
     * Additional critical database indexes for performance optimization
     * 
     * Run with: php artisan db:seed --class=CreateAdditionalPerformanceIndexesSeeder
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Borrowing-related indexes (Critical for queries)
            $this->createIndexIfNotExists('borrowings', 'idx_borrowing_approval_status_due', [
                'approval_status', 'due_at'
            ], 'Borrowing status and due date filtering');

            $this->createIndexIfNotExists('borrowings', 'idx_borrowing_returned_at', [
                'returned_at'
            ], 'Returned borrowing queries');

            $this->createIndexIfNotExists('borrowings', 'idx_borrowing_user_archive', [
                'user_id', 'archive_record_id'
            ], 'User borrowing history');

            // Archive Record relationships
            $this->createIndexIfNotExists('archive_records', 'idx_archive_record_item_id', [
                'archive_record_item_id'
            ], 'Archive record item lookups');

            $this->createIndexIfNotExists('archive_records', 'idx_archive_record_storage_id', [
                'storage_id'
            ], 'Storage archive records');

            // Document indexes
            $this->createIndexIfNotExists('documents', 'idx_documents_doc_type_id', [
                'doc_type_id'
            ], 'Document type lookups');

            // Shelf and Box relationships
            $this->createIndexIfNotExists('shelves', 'idx_shelves_archival_id', [
                'archival_id'
            ], 'Archival shelves');

            $this->createIndexIfNotExists('boxes', 'idx_boxes_storage_id', [
                'storage_id'
            ], 'Storage boxes');

            // User organization relationships
            $this->createIndexIfNotExists('model_has_roles', 'idx_model_has_roles_composite', [
                'model_id', 'role_id'
            ], 'User role lookups');

            // Organization and Archival
            $this->createIndexIfNotExists('organizations', 'idx_organizations_name', [
                'name'
            ], 'Organization name search');

            $this->createIndexIfNotExists('archivals', 'idx_archivals_name', [
                'name'
            ], 'Archival name search');

            // Complex query indexes
            $this->createIndexIfNotExists('borrowings', 'idx_borrowing_composite_query', [
                'archive_record_id', 'user_id', 'approval_status', 'created_at'
            ], 'Complex borrowing queries');

            $this->createIndexIfNotExists('archive_records', 'idx_archive_record_org_item', [
                'organization_id', 'archive_record_item_id'
            ], 'Organization and item filtering');

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->command->info('✅ All additional performance indexes created successfully!');

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
            $this->command->info("→ Table '{$table}' does not exist, skipping");
            return;
        }

        $columnList = implode(', ', $columns);
        
        // Check if index exists
        $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$indexName]);
        
        if (empty($indexes)) {
            $columnString = implode('`,`', $columns);
            $sql = "CREATE INDEX {$indexName} ON {$table} (`{$columnString}`)";
            
            try {
                DB::statement($sql);
                $this->command->info("✓ Index '{$indexName}' on {$table}({$columnList})");
            } catch (\Exception $e) {
                $this->command->warn("⚠️ Could not create '{$indexName}': {$e->getMessage()}");
            }
        } else {
            $this->command->info("→ '{$indexName}' already exists");
        }
    }
}
