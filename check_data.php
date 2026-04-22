<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check for duplicates and all records for this archive record
$record = App\Models\ArchiveRecord::where('code', '1')
    ->orWhere('id', 1)
    ->first();

if ($record) {
    echo "Archive record: {$record->id} - {$record->name}\n";
    $docs = App\Models\Document::where('archive_record_id', $record->id)
        ->orderBy('stt')
        ->get(['id','stt','document_code','description','total_pages','keywords','note','file_count']);
    echo "Total docs: " . $docs->count() . "\n\n";
    foreach($docs as $d) {
        echo "id={$d->id} stt={$d->stt} code={$d->document_code} | desc={$d->description} | pages={$d->total_pages} | kw=" . var_export($d->keywords, true) . " | note={$d->note} | fc={$d->file_count}\n";
    }
} else {
    echo "No archive record found\n";
    // Just show latest 30
    $docs = App\Models\Document::orderBy('id','desc')->take(30)->get(['id','stt','document_code','description','total_pages','keywords','archive_record_id']);
    foreach($docs as $d) {
        echo "id={$d->id} stt={$d->stt} code={$d->document_code} | desc={$d->description} | pages={$d->total_pages} | kw=" . var_export($d->keywords, true) . " | ar_id={$d->archive_record_id}\n";
    }
}
