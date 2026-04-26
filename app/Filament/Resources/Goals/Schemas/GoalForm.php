<?php

namespace App\Filament\Resources\Goals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GoalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(auth()->id()),

                TextInput::make('title')
                    ->label('Target Impian (Contoh: Beli Laptop)')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('target_amount')
                    ->label('Target Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                TextInput::make('current_amount')
                    ->label('Tabungan Terkumpul Saat Ini')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),

                DatePicker::make('deadline')
                    ->label('Target Tanggal (Opsional)'),
            ]);
    }
}
