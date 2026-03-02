<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

     protected static ?string $navigationGroup = 'shop';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Thương hiệu'; //Đổi tên text hiển thị
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Không hiển thị trong sidebar
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                  ->schema([
                        Forms\Components\Section::make([
                        
                            Forms\Components\TextInput::make(name:'name')
                            ->required()
                          //  ->live(onBlur:true) //Hàm này gửi giá trị nhập trực tiếp đến server khi người dùng nhập
                          //  ->unique()
                            ->afterStateUpdated(function(string $operation, $state, Forms\Set $set)
                                { //chức năng tạo slug tự động
                                    if($operation != 'create'){
                                        return;
                                }
                                $set('slug', Str::slug($state));
                                }),
                            Forms\Components\TextInput::make(name:'slug')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(table: Brand::class, column:'slug', ignoreRecord:true),
                            
                            Forms\Components\TextInput::make(name:'url')
                                ->label(label: 'Website Url')
                                ->required()
                            //    ->unique()
                            ->columnSpan(span:'full'),

                            Forms\Components\MarkdownEditor::make(name:'description')
                                ->columnSpan(span:'full')
                            

                        ]) ->columns(columns:2)
                    ]),

                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make(heading:'Status')
                                ->schema([
                                    Forms\Components\Toggle::make(name:'is_visible')
                                ->label('Visibility')
                                ->helperText(text:'Enable or disable brand visibility')
                                ->default(state:true),

                                    
                                ]),
                            Forms\Components\Group::make()
                                ->schema([
                                    Forms\Components\Section::make(heading:'Color')
                                        ->schema([
                                            Forms\Components\ColorPicker::make(name:'primary_hex')
                                                ->label('Primary Color')
                                            
                                        ])
                                ])
                        ])
                        
                 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(name:'name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make(name:'slug'),

                Tables\Columns\TextColumn::make(name:'url')
                    ->label(label:'Website Url')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ColorColumn::make(name:'primary_hex')
                    ->label(label:'Frimary Color'),

                Tables\Columns\TextColumn::make(name:'description'),

                Tables\Columns\IconColumn::make(name:'is_visible')
                    ->boolean()
                    ->sortable()
                    ->label(label:'Visibility'),
                Tables\Columns\TextColumn::make(name:'updated_at')
                    ->date()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }    
}
