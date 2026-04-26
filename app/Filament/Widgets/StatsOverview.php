<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        $userId = auth()->id();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // 1. Hitung Total Pemasukan Keseluruhan
        $totalIncome = Transaction::where('user_id', $userId)
            ->whereHas('category', function ($query) {
                $query->where('type', 'income');
            })->sum('amount');

        // 2. Hitung Total Pengeluaran Keseluruhan
        $totalExpense = Transaction::where('user_id', $userId)
            ->whereHas('category', function ($query) {
                $query->where('type', 'expense');
            })->sum('amount');

        // 3. Saldo Saat Ini (Pemasukan - Pengeluaran)
        $currentBalance = $totalIncome - $totalExpense;

        // 4. Pemasukan Khusus Bulan Ini
        $incomeThisMonth = Transaction::where('user_id', $userId)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereHas('category', function ($query) {
                $query->where('type', 'income');
            })->sum('amount');

        // 5. Pengeluaran Khusus Bulan Ini
        $expenseThisMonth = Transaction::where('user_id', $userId)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereHas('category', function ($query) {
                $query->where('type', 'expense');
            })->sum('amount');

        return [
            Stat::make('Total Saldo', 'Rp ' . number_format($currentBalance, 0, ',', '.'))
                ->description('Sisa uang lu saat ini')
                ->descriptionIcon('heroicon-m-wallet')
                ->color($currentBalance >= 0 ? 'success' : 'danger'), // Kalau minus warnanya merah

            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($incomeThisMonth, 0, ',', '.'))
                ->description('Uang masuk di bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Ini garis grafik pemanis (sparkline)

            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($expenseThisMonth, 0, ',', '.'))
                ->description('Uang keluar di bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([17, 16, 14, 15, 14, 13, 12]), // Sparkline pemanis
        ];
    }
}
