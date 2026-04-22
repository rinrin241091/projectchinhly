<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$docs = App\Models\Document::where('archive_record_id', 909)
    ->get(['document_code','security_level','copy_type']);
foreach($docs as $d) {
    echo $d->document_code . ' | security_level=' . var_export($d->security_level, true) . ' | copy_type=' . var_export($d->copy_type, true) . PHP_EOL;
}
