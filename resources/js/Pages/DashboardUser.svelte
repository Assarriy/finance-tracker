<script>
    import { router } from "@inertiajs/svelte";

    // Tangkap data asli dari Laravel
    let { userName, totalBalance, transactions, goals, categories } = $props();

    const formatRp = (angka) => {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0,
        }).format(angka);
    };

    // State untuk ngatur Modal Form
    let showModal = $state(false);
    let formData = $state({
        category_id: "",
        amount: "",
        date: new Date().toISOString().split("T")[0], // Set default hari ini
        note: "",
    });

    // Fungsi submit transaksi baru tanpa reload
    function submitTransaction(e) {
        e.preventDefault();

        router.post("/transactions", formData, {
            preserveScroll: true,
            onSuccess: () => {
                showModal = false;
                formData.amount = "";
                formData.note = "";
            },
        });
    }
</script>

<div class="min-h-screen p-6 max-w-5xl mx-auto flex flex-col gap-8">
    <header class="flex justify-between items-center">
        <h1 class="text-2xl font-bold tracking-tight">Halo, {userName}! 👋</h1>
        <button
            onclick={() => (showModal = true)}
            class="bg-gray-900 cursor-pointer text-white px-5 py-2.5 rounded-full font-medium hover:bg-gray-800 transition"
        >
            + Catat Duit
        </button>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 flex flex-col gap-6">
            <div
                class="bg-gradient-to-br from-gray-900 to-gray-800 p-8 rounded-3xl text-white shadow-lg"
            >
                <p class="text-gray-400 font-medium mb-1">
                    Total Saldo Saat Ini
                </p>
                <h2 class="text-4xl md:text-5xl font-bold tracking-tight">
                    {formatRp(totalBalance)}
                </h2>
            </div>

            <div
                class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100"
            >
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-lg">Riwayat Terakhir</h3>
                    <a
                        href="/admin/transactions"
                        class="text-sm text-blue-600 hover:underline"
                        >Lihat Semua di Admin</a
                    >
                </div>

                <div class="flex flex-col gap-4">
                    {#if transactions.length === 0}
                        <p class="text-gray-500 text-center py-4">
                            Belum ada transaksi bro. Yuk catat!
                        </p>
                    {:else}
                        {#each transactions as trx}
                            <div
                                class="flex justify-between items-center p-3 hover:bg-gray-50 rounded-xl transition"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-xl
                                        {trx.category.type === 'income'
                                            ? 'bg-green-100 text-green-600'
                                            : 'bg-red-100 text-red-600'}"
                                    >
                                        {trx.category.type === "income"
                                            ? "↓"
                                            : "↑"}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">
                                            {trx.category.name}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {new Date(
                                                trx.date,
                                            ).toLocaleDateString("id-ID")}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="font-bold {trx.category.type ===
                                        'income'
                                            ? 'text-green-600'
                                            : 'text-gray-900'}"
                                    >
                                        {trx.category.type === "income"
                                            ? "+"
                                            : "-"}{formatRp(trx.amount)}
                                    </p>
                                    {#if trx.note}
                                        <p
                                            class="text-xs text-gray-500 truncate w-24 md:w-32"
                                        >
                                            {trx.note}
                                        </p>
                                    {/if}
                                </div>
                            </div>
                        {/each}
                    {/if}
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <h3 class="font-bold text-lg px-2">Target Tabungan 🎯</h3>

            {#if goals.length === 0}
                <div
                    class="bg-white p-6 rounded-3xl border border-dashed border-gray-300 text-center text-gray-500"
                >
                    Belum ada target nih. Tambahin di panel admin.
                </div>
            {:else}
                {#each goals as goal}
                    {@const progress = Math.min(
                        Math.round(
                            (goal.current_amount / goal.target_amount) * 100,
                        ),
                        100,
                    )}

                    <div
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-center"
                    >
                        <p class="text-gray-900 font-bold">{goal.title}</p>
                        <p class="text-gray-500 text-xs mt-1">
                            {formatRp(goal.current_amount)} / {formatRp(
                                goal.target_amount,
                            )}
                        </p>

                        <div class="mt-3 w-full bg-gray-100 rounded-full h-3">
                            <div
                                class="bg-gray-900 h-3 rounded-full transition-all duration-500"
                                style="width: {progress}%"
                            ></div>
                        </div>
                        <p
                            class="text-right text-xs font-bold text-gray-700 mt-2"
                        >
                            {progress}%
                        </p>
                    </div>
                {/each}
            {/if}
        </div>
    </div>
</div>

{#if showModal}
    <div
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-all"
    >
        <div
            class="bg-white rounded-3xl w-full max-w-md p-6 shadow-xl transform transition-all"
        >
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Catat Transaksi Baru</h2>
                <button
                    onclick={() => (showModal = false)}
                    class="text-gray-400 hover:text-gray-900 text-2xl font-bold cursor-pointer"
                    >&times;</button
                >
            </div>

            <form onsubmit={submitTransaction} class="flex flex-col gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Kategori</label
                    >
                    <select
                        bind:value={formData.category_id}
                        required
                        class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-gray-900 outline-none bg-white"
                    >
                        <option value="" disabled selected
                            >-- Pilih Kategori --</option
                        >
                        {#each categories as cat}
                            <option value={cat.id}
                                >{cat.name} ({cat.type === "income"
                                    ? "Pemasukan"
                                    : "Pengeluaran"})</option
                            >
                        {/each}
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Nominal (Rp)</label
                    >
                    <input
                        type="number"
                        bind:value={formData.amount}
                        required
                        min="1"
                        placeholder="Misal: 50000"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-gray-900 outline-none"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Tanggal</label
                    >
                    <input
                        type="date"
                        bind:value={formData.date}
                        required
                        class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-gray-900 outline-none"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Catatan (Opsional)</label
                    >
                    <textarea
                        bind:value={formData.note}
                        rows="2"
                        placeholder="Nongkrong di warkop..."
                        class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-gray-900 outline-none"
                    ></textarea>
                </div>

                <div class="mt-4 flex gap-3">
                    <button
                        type="button"
                        onclick={() => (showModal = false)}
                        class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="flex-1 bg-gray-900 text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition cursor-pointer"
                    >
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
{/if}
