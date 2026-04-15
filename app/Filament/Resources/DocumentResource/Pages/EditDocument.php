<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure doc_type_id is set before updating the record
        if (empty($data['doc_type_id'])) {
            $firstDocType = \App\Models\DocType::orderBy('id')->first();
            if (!$firstDocType) {
                $firstDocType = \App\Models\DocType::create([
                    'name' => 'Văn bản thường',
                    'description' => 'Loại tài liệu mặc định'
                ]);
            }
            $data['doc_type_id'] = $firstDocType->id;
        }

        if (! isset($data['description']) || $data['description'] === null) {
            $data['description'] = '';
        }

        // Merge page_number_from and page_number_to
        $pageFrom = isset($data['page_number_from']) ? trim($data['page_number_from']) : null;
        $pageTo = isset($data['page_number_to']) ? trim($data['page_number_to']) : null;
        $hasPageFrom = $pageFrom !== null && $pageFrom !== '';
        $hasPageTo = $pageTo !== null && $pageTo !== '';

        if ($hasPageFrom) {
            $data['page_number_from'] = $pageFrom;
        }

        if ($hasPageTo) {
            $data['page_number_to'] = $pageTo;
        }

        if ($hasPageFrom && $hasPageTo) {
            $data['page_number'] = $pageFrom . '-' . $pageTo;
        } elseif ($hasPageFrom) {
            $data['page_number'] = $pageFrom;
        } elseif ($hasPageTo) {
            $data['page_number'] = $pageTo;
        }

        return $data;
    }
}

