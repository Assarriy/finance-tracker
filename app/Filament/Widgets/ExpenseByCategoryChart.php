<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class ExpenseByCategoryChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $heading = 'Pengeluaran per Kategori';

    protected function getData(): array
    {
        $userId = auth()->id();

        // Ambil kategori 'expense' (pengeluaran) milik user yang login, 
        // dan langsung jumlahkan total amount dari relasi transactions-nya
        $categories = Category::where('user_id', $userId)
            ->where('type', 'expense')
            ->withSum('transactions', 'amount')
            ->get();

        $labels = [];
        $data = [];

        // Bikin palet warna estetik (kode warna heksadesimal)
        $colors = ['#f87171', '#fb923c', '#facc15', '#4ade80', '#2dd4bf', '#3b82f6', '#c084fc', '#fb7185'];

        foreach ($categories as $category) {
            // Cuma nampilin kategori yang ada pengeluarannya
            if ($category->transactions_sum_amount > 0) {
                $labels[] = $category->name;
                $data[] = $category->transactions_sum_amount;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pengeluaran',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
