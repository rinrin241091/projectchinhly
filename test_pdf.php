<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ArchiveRecord;
use Barryvdh\DomPDF\Facade\Pdf;

try {
    // Get the first archive record for testing
    $archiveRecord = ArchiveRecord::with(['documents.docType', 'organization'])->first();
    
    if ($archiveRecord) {
        echo "Testing PDF export for record: " . $archiveRecord->title . "\n";
        
        $pdf = Pdf::loadView('pdf.document-list', [
            'archiveRecord' => $archiveRecord,
            'documents' => $archiveRecord->documents
        ]);
        
        // Configure PDF options for Vietnamese font support
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);
        
        // Save to file for testing
        $filename = "test_document_list.pdf";
        $pdf->save($filename);
        
        echo "PDF generated successfully: " . $filename . "\n";
        echo "File size: " . filesize($filename) . " bytes\n";
    } else {
        echo "No archive records found for testing.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
