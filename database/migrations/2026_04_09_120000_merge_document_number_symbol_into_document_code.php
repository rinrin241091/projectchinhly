<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('documents')
            ->select('id', 'document_number', 'document_symbol', 'document_code')
            ->orderBy('id')
            ->chunkById(500, function ($documents): void {
                foreach ($documents as $document) {
                    $existingCode = trim((string) ($document->document_code ?? ''));
                    if ($existingCode !== '') {
                        continue;
                    }

                    $number = trim((string) ($document->document_number ?? ''));
                    $symbol = trim((string) ($document->document_symbol ?? ''));
                    $merged = trim(collect([$number, $symbol])->filter()->implode('/'));

                    if ($merged === '') {
                        continue;
                    }

                    DB::table('documents')
                        ->where('id', $document->id)
                        ->update(['document_code' => $merged]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data migration: keep merged values in document_code.
    }
};
