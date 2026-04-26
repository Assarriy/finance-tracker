<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(auth()->id()),

                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->label('Kategori')
                    ->native(false)
                    ->searchable(),

                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->label('Nominal'),

                DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->label('Tanggal'),

                Textarea::make('note')
                    ->label('Catatan (Opsional)')
                    ->columnSpanFull(),
            ]);
    }
}
