<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\ArchiveRecord;
use App\Models\DocType;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BulkCreateDocuments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Tạo nhiều văn bản';
    protected static ?string $title = 'Tạo nhiều văn bản';
    protected static ?string $navigationGroup = 'Nhập liệu - Biên mục';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.pages.bulk-create-documents';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Traits\RoleBasedPermissions::canCreate();
    }

    public static function canAccess(): bool
    {
        return \App\Traits\RoleBasedPermissions::canCreate();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {
        return [
            Group::make()
                ->schema([
                    Section::make()
                        ->heading('Thêm nhiều văn bản/tài liệu')
                        ->description('Chọn hồ sơ lưu trữ và tạo danh sách tài liệu cùng một lúc.')
                        ->columns(1)
                        ->schema([
                            Select::make('archive_record_id')
                                ->label('Chọn hồ sơ lưu trữ')
                                ->options(function () {
                                    $organizationId = session('selected_archival_id');
                                    return $organizationId
                                        ? ArchiveRecord::where('organization_id', $organizationId)
                                            ->orderBy('code')
                                            ->get()
                                            ->mapWithKeys(fn ($record) => [$record->id => trim(collect([$record->code, $record->title])->filter()->implode(' - '))])
                                            ->toArray()
                                        : [];
                                })
                                ->required()
                                ->searchable()
                                ->preload()
                                ->default(fn () => session('selected_archive_record_id'))
                                ->reactive(),

                            Repeater::make('documents')
                                ->statePath('documents')
                                ->label('Danh sách văn bản/tài liệu')
                                ->schema([
                                    TextInput::make('document_code')
                                        ->label('Số, ký hiệu')
                                        ->required(),

                                    TextInput::make('description')
                                        ->label('Trích yếu nội dung văn bản')
                                        ->required(),

                                    Select::make('doc_type_id')
                                        ->label('Loại hồ sơ')
                                        ->options(DocType::pluck('name', 'id')->toArray())
                                        ->required(),

                                    Select::make('copy_type')
                                        ->label('Loại bản')
                                        ->options([
                                            'Bản chính' => 'Bản chính',
                                            'Bản sao' => 'Bản sao',
                                        ]),

                                    DatePicker::make('document_date')
                                        ->label('Ngày tháng văn bản'),

                                    TextInput::make('page_number_from')
                                        ->label('Từ trang số')
                                        ->numeric()
                                        ->minValue(0),

                                    TextInput::make('page_number_to')
                                        ->label('Đến trang số')
                                        ->numeric()
                                        ->minValue(0),

                                    Select::make('security_level')
                                        ->label('Độ mật (nếu chọn)')
                                        ->options([
                                            'mật' => 'Mật',
                                            'tuyệt mật' => 'Tuyệt mật',
                                            'tối mật' => 'Tối mật',
                                        ])
                                        ->placeholder('Để trống = Thường')
                                        ->nullable(),
                                ])
                                ->minItems(1)
                                ->required(),
                        ]),
                ]),
        ];
    }

    public function createDocuments(): void
    {
        $data = $this->form->getState();

        $currentMaxStt = Document::query()
            ->where('archive_record_id', $data['archive_record_id'])
            ->max('stt') ?: 0;

        foreach ($data['documents'] as $document) {
            $currentMaxStt++;

            $pageNumber = null;
            if (!empty($document['page_number_from']) && !empty($document['page_number_to'])) {
                $pageNumber = $document['page_number_from'] . '-' . $document['page_number_to'];
            } elseif (!empty($document['page_number_from'])) {
                $pageNumber = $document['page_number_from'];
            } elseif (!empty($document['page_number_to'])) {
                $pageNumber = $document['page_number_to'];
            }

            Document::create([
                'archive_record_id' => $data['archive_record_id'],
                'doc_type_id' => $document['doc_type_id'],
                'stt' => $currentMaxStt,
                'document_code' => $document['document_code'] ?? null,
                'description' => $document['description'] ?? '',
                'document_date' => $document['document_date'] ?? null,
                'copy_type' => $document['copy_type'] ?? null,
                'page_number' => $pageNumber,
                'security_level' => $document['security_level'] ?? 'thường',
            ]);
        }

        Notification::make()
            ->title('Thành công')
            ->body('Đã tạo danh sách văn bản/tài liệu thành công!')
            ->success()
            ->duration(3000)
            ->send();

        $this->redirect(DocumentResource::getUrl('index'));
    }
}
