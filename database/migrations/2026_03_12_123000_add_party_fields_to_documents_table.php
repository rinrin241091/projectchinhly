<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('document_number')->nullable()->after('doc_type_id');
            $table->string('document_symbol')->nullable()->after('document_number');
            $table->string('issuing_agency')->nullable()->after('document_date');
            $table->string('security_level')->nullable()->after('author');
            $table->string('copy_type')->nullable()->after('security_level');
            $table->unsignedInteger('total_pages')->nullable()->after('page_number');
            $table->unsignedInteger('file_count')->nullable()->after('total_pages');
            $table->string('file_name')->nullable()->after('file_count');
            $table->string('document_duration')->nullable()->after('file_name');
            $table->string('usage_mode')->nullable()->after('document_duration');
            $table->text('keywords')->nullable()->after('usage_mode');
            $table->string('language')->nullable()->after('keywords');
            $table->string('handwritten')->nullable()->after('language');
            $table->string('topic')->nullable()->after('handwritten');
            $table->string('information_code')->nullable()->after('topic');
            $table->string('reliability_level')->nullable()->after('information_code');
            $table->string('physical_condition')->nullable()->after('reliability_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'document_number',
                'document_symbol',
                'issuing_agency',
                'security_level',
                'copy_type',
                'total_pages',
                'file_count',
                'file_name',
                'document_duration',
                'usage_mode',
                'keywords',
                'language',
                'handwritten',
                'topic',
                'information_code',
                'reliability_level',
                'physical_condition',
            ]);
        });
    }
};
