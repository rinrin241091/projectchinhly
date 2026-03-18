<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Contracts\View\View;

class DocumentQrController extends Controller
{
    public function preview(Document $document): View
    {
        return view('documents.qr-preview', [
            'document' => $document,
            'qrText' => $document->load('archive_record.box.shelf')->getQrTextPayload(),
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . rawurlencode($document->getQrTextPayload()),
        ]);
    }

    public function show(Document $document): View
    {
        $document->load([
            'archive_record.box.shelf',
        ]);

        $record = $document->archive_record;
        $box = $record?->box;
        $shelf = $box?->shelf;

        return view('documents.qr-info', [
            'document' => $document,
            'documentName' => trim((string) ($document->description ?: $document->document_code ?: $document->document_number ?: ('Tài liệu #' . $document->id))),
            'recordName' => trim((string) ($record?->title ?: $record?->code ?: ($record?->reference_code ?: '-'))),
            'boxName' => trim((string) ($box?->description ?: $box?->code ?: '-')),
            'shelfName' => trim((string) ($shelf?->name ?: $shelf?->description ?: $shelf?->code ?: '-')),
        ]);
    }
}
