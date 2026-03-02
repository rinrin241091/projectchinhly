<?php
namespace App\Filament\Resources;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\ProductTypeEnum;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'shop';  //Tạo gộp nhóm menu bên trái

    protected static ?string $navigationIcon = 'heroicon-o-bolt'; //Đổi icon

    protected static ?string $navigationLabel = 'Sản phẩm'; //Đổi tên text hiển thị
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Không hiển thị trong sidebar
    }
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                        ->schema([
                            
                            Forms\Components\TextInput::make(name:'name')
                                ->required()
                                ->live(onBlur:true) //Hàm này gửi giá trị nhập trực tiếp đến server khi người dùng nhập
                                ->unique()
                                ->afterStateUpdated(function(string $operation, $state, Forms\Set $set){ //chức năng tạo slug tự động
                                    if($operation != 'create'){
                                        return;
                                    }
                                    $set('slug', Str::slug($state));

                                }),
                            Forms\Components\TextInput::make(name:'slug')
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->unique(table: Product::class, column:'slug', ignoreRecord:true),

                            Forms\Components\MarkdownEditor::make(name:'description')->columnSpan(span:'full'), //tách gộp ô
                        ]) ->columns(columns:2),

                        Forms\Components\Section::make(heading:'Pricing & Inventory')
                        ->schema([
                            
                            Forms\Components\TextInput::make(name:'sku')
                                ->label(label: "SKU (Stock Keeping Unit)") //Tạo nhãn hiển thị cho trường trong form
                                ->unique()
                                ->required(), 

                            Forms\Components\TextInput::make(name:'price')
                                ->numeric()
                                ->rules(rules:'regex:/^\d{1,6}(\.\d{0,2})?$/')
                                ->required(),

                            Forms\Components\TextInput::make(name:'quantity')
                                ->numeric()
                                ->minValue(value:0)
                                ->maxValue(value:100)
                                ->required(),

                            Forms\Components\Select::make(name:'type')
                                ->options([
                                    'downloadable'=>ProductTypeEnum::DOWNLOADABLE->value,
                                    'deliverable'=>ProductTypeEnum::DELIVERABLE->value,
                                ])->required()
                            
                        ]) ->columns(columns:2)

                    ]),
                    Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(heading:'Status')
                        ->schema([
                            
                            Forms\Components\Toggle::make(name:'is_visible')
                                ->label('Visibility')
                                ->helperText(text:'Enable or disable product visibility')
                                ->default(state:true),

                            Forms\Components\Toggle::make(name:'is_featured')
                                ->label(label:'Featured')
                                ->helperText(text:'Enable or disable products featured status'),

                            Forms\Components\DatePicker::make(name:'published_at')
                                ->label(label:'Availability')
                                ->default(now()),

                        ]),
                        Forms\Components\Section::make(heading:'Image')
                            ->schema([
                                Forms\Components\FileUpload::make(name:'image')
                                    ->directory(directory:'form-attachments')
                                    ->preserveFilenames()
                                    ->image()
                                    ->imageEditor()
                            ])->collapsible(),

                            Forms\Components\Section::make(heading:'Associations')
                            ->schema([
                                Forms\Components\Select::make(name:'brand_id')
                                ->relationship(name:'brand',titleAttribute:'name')
                            ]),

                    ]),

      
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make(name:'image'),

                Tables\Columns\TextColumn::make(name:'name')
                    ->searchable()
                    ->sortable(),  //Hiển thị cột trong bảng

                Tables\Columns\TextColumn::make(name:'brand.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make(name:'is_visible')
                    ->boolean()
                    ->sortable()
                    ->toggleable()
                    ->label('visibility'),

                Tables\Columns\TextColumn::make(name:'price')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make(name:'quantity')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make(name:'published_at')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make(name:'type'),

            ])
            ->filters([
                Tables\Filters\TernaryFilter::make(name:'is_visible') //Bộ lọc dữ liệu 
                    ->label(label:'Visibility')
                    ->boolean()
                    ->trueLabel(trueLabel:'Only Visible Products')
                    ->falseLabel(falseLabel:'Only hidden Products')
                    ->native(condition:false),

                Tables\Filters\SelectFilter::make(name:'brand')
                    ->relationship(name:'brand',titleAttribute:'name')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }    
}
