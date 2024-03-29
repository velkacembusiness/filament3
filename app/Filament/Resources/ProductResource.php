<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'All products';

    //protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    protected static array $statuses = [ // it should be static because others methods are static you need to use self
        'in stock' => 'in stock',
        'sold out' => 'sold out',
        'coming soon' => 'coming soon',
    ];

    public static function getNavigationBadge(): ?string
    {
        return Product::whereDate('created_at', today())->count() ? 'NEW' : '';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
                Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('Main data')
                ->schema([
                Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('price')->required()->rule('numeric'),
                //Forms\Components\Select::make('status')
            ]),
            Forms\Components\Tabs\Tab::make('Additional data')
                ->schema([
                Forms\Components\Radio::make('status') 
                    ->options(self::$statuses),
                Forms\Components\Select::make('category_id') 
                    ->relationship('category', 'name'), 
                Forms\Components\Select::make('tags') 
                    ->relationship('tags', 'name') 
                    ->multiple(),
                Forms\Components\RichEditor::make('description') 
                    ->required(),
                ]),
            ])
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextInputColumn::make('name')->rules(['required', 'min:3'])->sortable()->searchable(), 
                Tables\Columns\TextColumn::make('price')->sortable()
                ->money('dzd') 
                ->getStateUsing(function (Product $record): float { 
                    return $record->price / 100; 
                })
                ->alignment(Alignment::End),
                Tables\Columns\ToggleColumn::make('is_active'),
                Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'in stock' => 'primary',
                    'sold out' => 'danger',
                    'coming soon' => 'info',
                }), 
                Tables\Columns\TextColumn::make('category.name')
                ->label('Category name'),
                Tables\Columns\TextColumn::make('tags.name')
                ->badge(),
            ])
            ->defaultSort('price', 'desc') 
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                ->options(self::$statuses),
                Tables\Filters\SelectFilter::make('category')
                ->relationship('category', 'name'),
                Tables\Filters\Filter::make('created_at')
                ->form([
                    Forms\Components\DatePicker::make('created_from'),
                    Forms\Components\DatePicker::make('created_until'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TagsRelationManager::class,
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
