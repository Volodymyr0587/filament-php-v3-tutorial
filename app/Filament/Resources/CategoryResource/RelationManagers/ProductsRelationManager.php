<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Enums\ProductTypeEnum;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Products')
                    ->tabs([
                        Tab::make('Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->unique()
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    }),

                                TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(Product::class, 'slug', ignoreRecord: true),

                                MarkdownEditor::make('description')
                                    ->columnSpan('full')
                            ])->columns(),
                        Tab::make('Pricing & Inventory')
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->unique()
                                    ->required(),

                                TextInput::make('price')
                                    ->numeric()
                                    ->rules(['regex:/^\d{1,6}(\.\d{0,2})?$/'])
                                    ->required(),

                                TextInput::make('quantity')
                                    // ->rules(['integer', 'min:0']),
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required(),

                                Select::make('type')
                                    ->options([
                                        'downloadable' => ProductTypeEnum::DOWNLOADABLE->value,
                                        'deliverable' => ProductTypeEnum::DELIVERABLE->value
                                    ])->required()
                            ])->columns(2),
                        Tab::make('Additional Information')
                            ->schema([
                                Toggle::make('is_visible')
                                    ->label('Visibility')
                                    ->helperText('Enable or disable product visibility')
                                    ->default(true),

                                Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->helperText('Enable or disable product featured status'),

                                DateTimePicker::make('published_at')
                                    ->label('Availability')
                                    ->default(now()),

                                Select::make('categories')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->required(),
                                FileUpload::make('image')
                                    ->directory('form-attachments')
                                    ->preserveFilenames()
                                    ->image()
                                    ->imageEditor()
                                    ->columnSpanFull(),
                            ])->columns(2)
                    ])->columnSpanFull()
            ]);
    }


    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_visible')
                    ->sortable()
                    ->toggleable()
                    ->label('Visibility')
                    ->boolean(),

                TextColumn::make('price')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->date()
                    ->sortable(),

                TextColumn::make('type')
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
