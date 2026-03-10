<?php

namespace App\Filament\Resources;

use App\Traits\RoleBasedPermissions;
use App\Filament\Resources\BoxResource\Pages;
use App\Filament\Resources\BoxResource\RelationManagers;
use App\Models\Box;
use App\Models\Shelf;
use App\Models\Archival;
//use App\Models\Storage;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


use Filament\Tables\Actions\BulkAction; // hỗ trợ in nhãn
use Filament\Tables\Actions\Action;// hỗ trợ in nhãn
use Barryvdh\DomPDF\Facade\Pdf; // hỗ trợ in nhãn
use Illuminate\Support\Facades\Storage; // Hỗ trợ in nhãn

class BoxResource extends Resource
{
    use RoleBasedPermissions;
    protected static ?string $model = Box::class;
      protected static ?string $navigationLabel = 'Danh sách Hộp';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Khai thác - Thống kê';  //Tạo gộp nhóm menu bên trái

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('shelf_id')
                ->label('Chọn kệ chứa')
                ->options(function ($record) {
                    // 🔸 Nếu đang edit: lấy mã kho từ hộp hiện tại
                    $storageId = $record?->shelf?->storage?->id;

                    // 🔸 Nếu đang tạo mới, có thể load theo phông đang chọn
                    if (!$storageId && session()->has('selected_archival_id')) {
                        $archivalId = session('selected_archival_id');
                        return \App\Models\Shelf::whereHas('storage', function ($q) use ($archivalId) {
                            $q->where('archival_id', $archivalId);
                        })->pluck('description', 'id');
                    }

                    // 🔸 Nếu đã biết kho, chỉ lấy các kệ trong kho đó
                    if ($storageId) {
                        return \App\Models\Shelf::where('storage_id', $storageId)
                            ->pluck('description', 'id');
                    }

                    return [];
                })
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateHydrated(function ($state, callable $set, $record) {
                    // 🔹 Khi edit, nếu form chưa có state, hiển thị đúng kệ hiện tại
                    if (!$state && $record?->shelf?->id) {
                        $set('shelve_id', $record->shelf->id);
                    }
                }),
                Forms\Components\TextInput::make('code')
                ->label('Mã Hộp')
                ->required()
                ->maxLength(255),
                Forms\Components\TextInput::make('description')
                ->label('Tên/Mô tả hộp')
                ->maxLength(255),  
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(name:'code')
                    ->searchable()
                    ->sortable()
                    ->label('Mã hộp'),  //Hiển thị cột trong bảng
               
                Tables\Columns\TextColumn::make(name:'description')
                    ->searchable()
                    ->sortable()
                    ->label('Tên/ mô tả hộp'), 
                Tables\Columns\TextColumn::make(name:'type')
                    ->searchable()
                    ->sortable()
                    ->label('Loại'), 
                
                Tables\Columns\TextColumn::make(name:'shelf.description')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make(name:'shelf.storage.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make(name:'shelf.storage.archival.name')
                    ->label('Đơn vị lưu trữ')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make(name:'record_count')
                    ->sortable()
                    ->label('Số lượng hồ sơ')
                    ->default(0), 
                Tables\Columns\TextColumn::make(name:'page_count')
                    ->sortable()
                    ->label('Số lượng trang')
                    ->default(0),
            
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => static::canEdit(null)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => static::canDelete(null)),

                Action::make('xemTruocIn')
                    ->label('Xem nhãn hộp')
                    ->icon('heroicon-m-printer')
                    ->modalHeading('Xem nhãn hộp')
                    ->modalSubmitAction(false)
                    ->modalWidth('lg') // hoặc xl nếu cần rộng hơn
                    ->modalContent(function ($record) {
                        return view('exports.print-box-label', compact('record'));
                    })
               

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => static::canDelete(null)),
                    Tables\Actions\BulkAction::make('print_labels')
            ->label('In nhãn hộp')
            ->icon('heroicon-o-printer')
            ->action(function ($records) {
                $pdf = Pdf::loadView('exports.box-labels', [
                    'boxes' => $records,
                ]);
                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->stream();
                }, 'nhan-hop.pdf');
            })
            ->deselectRecordsAfterCompletion()
                ]),
            // XEM TRƯỚC KHI IN NHÃN HỘP 
                ])

            ->headerActions([
                Tables\Actions\CreateAction::make('bulkCreateBoxs')
                    ->label('Tạo hộp mới')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn () => route('filament.dashboard.pages.bulk-create-boxs'))
                    ->visible(fn() => static::canCreate()),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn() => static::canCreate()),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoxes::route('/'),
            'create' => Pages\CreateBox::route('/create'),
            'edit' => Pages\EditBox::route('/{record}/edit'),
        ];
    }    
}
