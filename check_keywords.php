<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$docs = App\Models\Document::where('archive_record_id', 909)
    ->orderBy('stt')
    ->get(['stt','document_code','keywords']);

echo "Total rows: " . $docs->count() . "\n\n";
foreach($docs as $d) {
    echo "STT {$d->stt} | Code {$d->document_code} | Keywords: " . var_export($d->keywords, true) . "\n";
}
