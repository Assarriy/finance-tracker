<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;

class LatestTransactions extends BaseWidget
{
    // Urutan ke-3 (di bawah Stats dan Chart)
    protected static ?int $sort = 3;

    // Bikin tabelnya memanjang full ke samping
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Cuma narik data transaksi milik user yang login
                Transaction::where('user_id', auth()->id())
                    ->latest('date')
                    ->latest('created_at')
                    ->limit(5) // Cuma nampilin 5 transaksi terakhir
            )
            ->columns([
                TextColumn::make('date')
                    ->date('d M Y')
                    ->label('Tanggal'),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge(), // Kasih background warna-warni bawaan Filament

                TextColumn::make('amount')
                    ->money('IDR', locale: 'id')
                    ->label('Nominal')
                    ->color(fn($record) => $record->category->type === 'income' ? 'success' : 'danger'),

                TextColumn::make('note')
                    ->limit(50)
                    ->label('Catatan'),
            ])
            ->paginated(false); // Hilangin tombol Next/Prev karena ini cuma ringkasan
    }
}