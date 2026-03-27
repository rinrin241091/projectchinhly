<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Inertia\Inertia;

class DocumentQrController extends Controller
{
    public function preview(Document $document)
    {
        return Inertia::render('Documents/QrPreview', [
            'document' => [
                'id' => $document->id,
            ],
            'qrText' => $document->load('archive_record.box.shelf')->getQrTextPayload(),
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . rawurlencode($document->getQrTextPayload()),
        ]);
    }

    public function show(Document $document)
    {
        $document->load([
            'archive_record.box.shelf',
        ]);

        $record = $document->archive_record;
        $box = $record?->box;
        $shelf = $box?->shelf;

        return Inertia::render('Documents/QrInfo', [
            'document' => [
                'id' => $document->id,
            ],
            'documentName' => trim((string) ($document->description ?: $document->document_code ?: $document->document_number ?: ('Tài liệu #' . $document->id))),
            'recordName' => trim((string) ($record?->title ?: $record?->code ?: ($record?->reference_code ?: '-'))),
            'boxName' => trim((string) ($box?->description ?: $box?->code ?: '-')),
            'shelfName' => trim((string) ($shelf?->name ?: $shelf?->description ?: $shelf?->code ?: '-')),
        ]);
    }
}
