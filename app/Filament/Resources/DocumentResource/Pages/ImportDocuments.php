<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Imports\DocumentsImport;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportDocuments extends Page
{
    protected static string $resource = DocumentResource::class;

    protected static string $view = 'filament.resources.document-resource.pages.import-documents';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Import Tài Liệu Từ CSV')
                    ->description('Tải lên file CSV với các cột: document_code, document_date, description, author, page_number, note, archive_record_reference, doc_type_name')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('File CSV')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'application/csv', 'text/plain'])
                            ->maxSize(10240)
                            ->disk('local')
                            ->directory('imports'),

                        Forms\Components\Select::make('organization_id')
                            ->label('Chọn Phông Lưu Trữ')
                            ->options(function () {
                                return \App\Models\Organization::pluck('name', 'id');
                            })
                            ->required()
                            ->disabled(fn () => !session()->has('selected_archival_id'))
                            ->default(fn () => session('selected_archival_id')),
                    ]),
            ])
            ->statePath('data');
    }

    public function import()
    {
        $data = $this->form->getState();
        
        try {
            $file = $data['file'];
            $organizationId = $data['organization_id'];
            
            Excel::import(new DocumentsImport($organizationId), $file);
            
            Notification::make()
                ->title('Import thành công')
                ->body('Dữ liệu tài liệu đã được import thành công!')
                ->success()
                ->send();
                
            return redirect()->route('filament.dashboard.resources.documents.index');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi import')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function save()
    {
        $data = $this->form->getState();
        
        try {
            $file = $data['file'];
            $organizationId = $data['organization_id'];
            
            // Store the file for later import
            $filePath = $file->store('pending-imports');
            
            // Store import details in session for later use
            session()->put('pending_import', [
                'file_path' => $filePath,
                'organization_id' => $organizationId,
                'original_name' => $file->getClientOriginalName()
            ]);
            
            Notification::make()
                ->title('Lưu thành công')
                ->body('File đã được lưu thành công! Sẵn sàng để import dữ liệu.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi lưu')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Quay lại')
                ->url(route('filament.dashboard.resources.documents.index'))
                ->color('gray'),
        ];
    }
}
