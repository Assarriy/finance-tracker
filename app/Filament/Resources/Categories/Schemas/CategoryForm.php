<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kategori')
                    ->placeholder('Contoh: Makan, Gaji, Bensin')
                    ->required()
                    ->maxLength(255),

                Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'income' => 'Pemasukan 💰',
                        'expense' => 'Pengeluaran 💸',
                    ])
                    ->required()
                    ->native(false), // Biar tampilan dropdown-nya lebih modern ala Tailwind
            ]);
    }
}
