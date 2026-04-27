<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Inertia\Inertia;

Route::get('/', function () {
    // Cek apakah user udah login (pakai auth bawaan Filament)
    if (!auth()->check()) {
        return redirect('/admin/login');
    }

    $user = auth()->user();

    // Hitung Saldo
    $income = $user->transactions()->whereHas('category', fn($q) => $q->where('type', 'income'))->sum('amount');
    $expense = $user->transactions()->whereHas('category', fn($q) => $q->where('type', 'expense'))->sum('amount');
    $totalBalance = $income - $expense;

    // Ambil data untuk frontend
    $latestTransactions = $user->transactions()->with('category')->latest('date')->latest('created_at')->take(5)->get();
    $goals = $user->goals()->get();
    $categories = $user->categories()->get();

    return Inertia::render('DashboardUser', [
        'userName' => $user->name,
        'totalBalance' => (int) $totalBalance,
        'transactions' => $latestTransactions,
        'goals' => $goals,
        'categories' => $categories
    ]);
});

// Route untuk nyimpen transaksi dari frontend Svelte
Route::post('/transactions', function (Request $request) {
    $validated = $request->validate([
        'category_id' => 'required|exists:categories,id',
        'amount' => 'required|numeric|min:1',
        'date' => 'required|date',
        'note' => 'nullable|string'
    ]);

    $validated['user_id'] = auth()->id();

    Transaction::create($validated);

    // Otomatis refresh data di frontend (Inertia magic)
    return redirect()->back();
});

// Route untuk nabung ke target (Goals)
Route::post('/goals/add-funds', function (Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'goal_id' => 'required|exists:goals,id',
        'amount' => 'required|numeric|min:1'
    ]);

    // Cari target tabungan milik user yang lagi login, terus tambahin nominalnya
    $goal = App\Models\Goal::where('user_id', auth()->id())
        ->findOrFail($validated['goal_id']);

    $goal->increment('current_amount', $validated['amount']);

    return redirect()->back();
});

Route::post('/goals', function (Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'target_amount' => 'required|numeric|min:1',
        'deadline' => 'nullable|date'
    ]);

    $validated['user_id'] = auth()->id();
    $validated['current_amount'] = 0;

    App\Models\Goal::create($validated);

    return redirect()->back();
});