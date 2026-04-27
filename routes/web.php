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

// Route khusus untuk AI Scanner
Route::post('/api/scan-receipt', function (Illuminate\Http\Request $request) {
    try {
        $request->validate(['receipt' => 'required|image|max:4096']);

        $image = $request->file('receipt');
        $base64Image = base64_encode(file_get_contents($image->path()));
        $mimeType = $image->getMimeType();

        $apiKey = env('GEMINI_API_KEY');

        // Pastikan API Key nggak kosong
        if (!$apiKey) {
            return response()->json(['error' => 'API Key Gemini belum disetting di .env bro!'], 500);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        // Tambahin withoutVerifying() buat nembus limitasi SSL localhost Windows
        $response = Http::withoutVerifying()->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Tugas lu adalah sebagai akuntan. Baca gambar struk belanja ini. Temukan "Total Harga" (angka saja tanpa titik/koma), "Tanggal" (format YYYY-MM-DD), dan "Catatan" (nama toko/tempat belanja). Kembalikan HANYA format JSON valid seperti ini: {"amount": 530000, "date": "2021-04-09", "note": "sy_beautyskin"}. Jangan tambahkan kata-kata lain, jangan pakai markdown block.'],

                        // INI YANG DIBENERIN: inlineData dan mimeType (harus ada huruf besarnya)
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Kalau Gemini nolak request-nya
        // Kalau Gemini nolak request-nya, TAMPILIN ERROR ASLINYA
        if ($response->failed()) {
            Log::error('Gemini API Error: ' . $response->body());
            return response()->json(['error' => 'Dari Google: ' . $response->body()], 500);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        $text = preg_replace('/```json|```/', '', $text);

        return response()->json(json_decode(trim($text), true));

    } catch (\Exception $e) {
        // Kalau errornya murni karena koneksi PHP/Laravel lu
        Log::error('Scan Receipt Error: ' . $e->getMessage());
        return response()->json(['error' => 'Koneksi PHP Mental: ' . $e->getMessage()], 500);
    }
});