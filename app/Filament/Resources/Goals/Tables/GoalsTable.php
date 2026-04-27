<?php

namespace App\Filament\Resources\Goals\Tables;

use App\Models\Goal;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GoalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Impian')
                    ->searchable(),

                TextColumn::make('current_amount')
                    ->label('Terkumpul')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('target_amount')
                    ->label('Target')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                // Kolom Persentase Progress Buatan Sendiri
                TextColumn::make('progress')
                    ->label('Progress')
                    ->getStateUsing(function (Goal $record) {
                        if ($record->target_amount == 0)
                            return 0;
                        return round(($record->current_amount / $record->target_amount) * 100, 1);
                    })
                    ->suffix('%')
                    ->badge()
                    ->color(fn($state) => $state >= 100 ? 'success' : 'warning'),

                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Tombol Custom buat Nabung Cepat
                Action::make('nabung')
                    ->label('Nabung')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal Tabungan Baru')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->action(function (Goal $record, array $data): void {
                        // Tambahin duit yang baru diinput ke tabungan yang udah ada
                        $record->update([
                            'current_amount' => $record->current_amount + $data['amount']
                        ]);

                        // Munculin notifikasi pop-up kalau sukses
                        Notification::make()
                            ->title('Mantap! Tabungan berhasil ditambah 💸')
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
