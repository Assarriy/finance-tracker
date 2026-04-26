<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal'),

                TextColumn::make('category.name')
                    ->searchable()
                    ->label('Kategori')
                    ->badge(),

                TextColumn::make('amount')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->label('Nominal')
                    ->color(fn($record) => $record->category->type === 'income' ? 'success' : 'danger'),

                TextColumn::make('note')
                    ->limit(30)
                    ->label('Catatan'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
