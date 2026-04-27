<script>
    import { router } from "@inertiajs/svelte";
    import axios from "axios";

    // Tangkap data dari controller
    let { userName, totalBalance, transactions, goals, categories } = $props();

    // Helper format duit
    const formatRp = (n) =>
        new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0,
        }).format(n);

    // --- STATE MANAGEMENT (Svelte 5 Runes) ---
    let showTrxModal = $state(false);
    let showGoalModal = $state(false);
    let showAddGoalModal = $state(false);
    let isScanning = $state(false); // State buat efek loading AI

    let selectedGoal = $state(null);
    let goalFundAmount = $state("");

    // Form data transaksi
    let trxForm = $state({
        category_id: "",
        amount: "",
        date: new Date().toISOString().split("T")[0],
        note: "",
    });

    // Form data goal baru
    let newGoalForm = $state({ title: "", target_amount: "", deadline: "" });

    // Insight Otomatis (Biar UI Rame & Pintar)
    let savingsRate = $derived(
        totalBalance > 0 ? Math.round((totalBalance / 5000000) * 100) : 0,
    );
    let statusPesan = $derived(
        totalBalance > 1000000
            ? "Kondisi keuangan lu sehat bro! 💪"
            : "Waduh, jajan dikurangin dulu ya. 😅",
    );

    // --- ACTIONS ---

    // 1. AI Scanner Request
    async function scanReceipt(e) {
        const file = e.target.files[0];
        if (!file) return;

        isScanning = true;
        const data = new FormData();
        data.append("receipt", file);

        try {
            const res = await axios.post("/api/scan-receipt", data);

            // Auto-fill form
            if (res.data.amount) trxForm.amount = res.data.amount;
            if (res.data.date) trxForm.date = res.data.date;
            if (res.data.note) trxForm.note = res.data.note;
        } catch (err) {
            let errorMessage = err.response?.data?.error || err.message;
            alert("Gagal bro! Detailnya: " + errorMessage);
        } finally {
            isScanning = false;
            e.target.value = ""; // Reset input
        }
    }

    // 2. Submit Transaksi Manual / Hasil Scan
    function submitTrx(e) {
        e.preventDefault();
        router.post("/transactions", trxForm, {
            preserveScroll: true,
            onSuccess: () => {
                showTrxModal = false;
                trxForm.amount = "";
                trxForm.note = "";
            },
        });
    }

    // 3. Buat Target Impian Baru
    function submitNewGoal(e) {
        e.preventDefault();
        router.post("/goals", newGoalForm, {
            preserveScroll: true,
            onSuccess: () => {
                showAddGoalModal = false;
                newGoalForm = { title: "", target_amount: "", deadline: "" };
            },
        });
    }

    // 4. Nabung ke Target yang Udah Ada
    function submitGoalFunds(e) {
        e.preventDefault();
        router.post(
            "/goals/add-funds",
            { goal_id: selectedGoal.id, amount: goalFundAmount },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showGoalModal = false;
                    goalFundAmount = "";
                },
            },
        );
    }
</script>

<div class="min-h-screen bg-gray-50 text-gray-900 font-sans pb-12">
    <div class="bg-gray-900 text-white pt-12 pb-24 px-6">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">
                    Halo, {userName}!
                </h1>
                <p class="text-gray-400 mt-1">{statusPesan}</p>
            </div>
            <div class="flex gap-3">
                <button
                    onclick={() => (showAddGoalModal = true)}
                    class="bg-white/10 hover:bg-white/20 px-5 py-2.5 rounded-2xl font-bold transition backdrop-blur-md border border-white/10 text-sm cursor-pointer"
                >
                    + Target
                </button>
                <button
                    onclick={() => (showTrxModal = true)}
                    class="bg-blue-600 hover:bg-blue-500 px-6 py-2.5 rounded-2xl font-bold transition shadow-lg shadow-blue-500/30 text-sm cursor-pointer"
                >
                    Catat Transaksi
                </button>
            </div>
        </div>
    </div>

    <main
        class="max-w-6xl mx-auto px-6 -mt-16 grid grid-cols-1 md:grid-cols-12 gap-6"
    >
        <div
            class="md:col-span-8 bg-white p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 border border-white flex flex-col justify-between overflow-hidden relative group"
        >
            <div class="relative z-10">
                <span
                    class="text-gray-500 font-bold uppercase tracking-widest text-xs"
                    >Dompet Saat Ini</span
                >
                <h2 class="text-5xl font-black mt-2 tracking-tighter">
                    {formatRp(totalBalance)}
                </h2>
                <div class="flex gap-4 mt-8">
                    <div
                        class="bg-green-50 px-4 py-2 rounded-xl text-green-700 font-bold text-sm"
                    >
                        ↑ Safe Zone
                    </div>
                    <div
                        class="bg-blue-50 px-4 py-2 rounded-xl text-blue-700 font-bold text-sm"
                    >
                        AI Scanner Ready ✨
                    </div>
                </div>
            </div>
            <div
                class="absolute -right-10 -bottom-10 w-48 h-48 bg-blue-50 rounded-full blur-3xl group-hover:bg-blue-100 transition-colors"
            ></div>
        </div>

        <div
            class="md:col-span-4 bg-gray-900 p-8 rounded-[2rem] text-white flex flex-col justify-between shadow-xl"
        >
            <div>
                <span
                    class="bg-blue-500 text-[10px] uppercase px-2 py-1 rounded-lg font-black"
                    >Smart Insight</span
                >
                <p class="mt-4 text-gray-300 leading-relaxed text-sm">
                    Berdasarkan saldo lu, lu udah mencapai <span
                        class="text-blue-400 font-bold">{savingsRate}%</span
                    > dari skor keamanan finansial minimum bulan ini.
                </p>
            </div>
            <div class="mt-6">
                <div
                    class="w-full bg-white/10 h-2 rounded-full overflow-hidden"
                >
                    <div
                        class="bg-blue-400 h-full rounded-full"
                        style="width: {savingsRate}%"
                    ></div>
                </div>
                <p
                    class="text-[10px] text-gray-500 mt-2 font-bold uppercase tracking-widest"
                >
                    Financial Score
                </p>
            </div>
        </div>

        <div
            class="md:col-span-7 bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100"
        >
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-black text-xl tracking-tight italic">
                    Riwayat Terakhir
                </h3>
                <a
                    href="/admin/transactions"
                    class="text-xs font-bold text-blue-600 hover:bg-blue-50 px-3 py-1 rounded-lg transition"
                    >Semua &rarr;</a
                >
            </div>
            <div class="space-y-4">
                {#if transactions.length === 0}
                    <p class="text-gray-500 text-sm text-center py-4">
                        Belum ada transaksi bro.
                    </p>
                {:else}
                    {#each transactions as trx}
                        <div
                            class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl hover:scale-[1.02] transition-transform"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-sm
                                    {trx.category.type === 'income'
                                        ? 'bg-green-500 text-white'
                                        : 'bg-red-50 text-red-500'}"
                                >
                                    {trx.category.type === "income"
                                        ? "💰"
                                        : "💸"}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">
                                        {trx.category.name}
                                    </p>
                                    <p
                                        class="text-[10px] text-gray-400 font-bold uppercase"
                                    >
                                        {new Date(trx.date).toLocaleDateString(
                                            "id-ID",
                                            { day: "2-digit", month: "short" },
                                        )}
                                    </p>
                                </div>
                            </div>
                            <p
                                class="font-black {trx.category.type ===
                                'income'
                                    ? 'text-green-600'
                                    : 'text-gray-900'}"
                            >
                                {trx.category.type === "income"
                                    ? "+"
                                    : "-"}{formatRp(trx.amount)}
                            </p>
                        </div>
                    {/each}
                {/if}
            </div>
        </div>

        <div class="md:col-span-5 flex flex-col gap-6">
            <h3 class="font-black text-xl tracking-tight italic px-2">
                Target Impian 🎯
            </h3>
            <div class="space-y-4">
                {#if goals.length === 0}
                    <div
                        class="bg-white p-6 rounded-[2rem] border border-dashed border-gray-300 text-center text-gray-500 text-sm font-bold"
                    >
                        Belum ada target nih bro. Gas bikin impian lu!
                    </div>
                {:else}
                    {#each goals as goal}
                        {@const p = Math.min(
                            Math.round(
                                (goal.current_amount / goal.target_amount) *
                                    100,
                            ),
                            100,
                        )}
                        <div
                            class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 relative overflow-hidden group"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="font-black text-gray-900">
                                        {goal.title}
                                    </h4>
                                    <p
                                        class="text-xs text-gray-500 mt-1 font-medium"
                                    >
                                        {formatRp(goal.current_amount)} / {formatRp(
                                            goal.target_amount,
                                        )}
                                    </p>
                                </div>
                                <span class="text-2xl"
                                    >{p >= 100 ? "🔥" : "🚀"}</span
                                >
                            </div>
                            <div
                                class="w-full bg-gray-100 h-3 rounded-full overflow-hidden"
                            >
                                <div
                                    class="bg-gray-900 h-full transition-all duration-1000"
                                    style="width: {p}%"
                                ></div>
                            </div>
                            <div class="flex justify-between items-center mt-4">
                                <p
                                    class="text-xs font-black text-gray-900 uppercase tracking-widest"
                                >
                                    {p}%
                                </p>
                                {#if p < 100}
                                    <button
                                        onclick={() => {
                                            selectedGoal = goal;
                                            showGoalModal = true;
                                        }}
                                        class="bg-blue-50 text-blue-600 text-[10px] font-black uppercase px-4 py-2 rounded-xl hover:bg-blue-600 hover:text-white transition cursor-pointer"
                                    >
                                        Nabung
                                    </button>
                                {:else}
                                    <span
                                        class="text-[10px] font-black text-green-600 uppercase"
                                        >Tercapai! 🎉</span
                                    >
                                {/if}
                            </div>
                        </div>
                    {/each}
                {/if}
            </div>
        </div>
    </main>
</div>

{#if showTrxModal}
    <div
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-md z-50 flex items-center justify-center p-4"
    >
        <div class="bg-white rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl">
            <h2 class="text-2xl font-black mb-6 tracking-tighter">
                Catat Transaksi
            </h2>

            <div
                class="mb-6 p-4 border-2 border-dashed border-blue-200 bg-blue-50/50 rounded-2xl text-center hover:border-blue-500 transition relative overflow-hidden"
            >
                {#if isScanning}
                    <div
                        class="flex flex-col items-center justify-center space-y-2 text-blue-600 py-2"
                    >
                        <svg
                            class="animate-spin h-6 w-6"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            ><circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle><path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path></svg
                        >
                        <span class="font-bold text-sm tracking-tight"
                            >Gemini lagi baca struk lu...</span
                        >
                    </div>
                {:else}
                    <label
                        class="cursor-pointer flex flex-col items-center text-blue-600 hover:text-blue-800 transition py-2"
                    >
                        <span class="text-3xl mb-1">📸</span>
                        <span class="font-bold text-sm tracking-tight"
                            >Auto-Fill pakai Struk (AI)</span
                        >
                        <input
                            type="file"
                            accept="image/*"
                            onchange={scanReceipt}
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        />
                    </label>
                {/if}
            </div>

            <form onsubmit={submitTrx} class="space-y-4">
                <select
                    bind:value={trxForm.category_id}
                    required
                    class="w-full bg-gray-100 rounded-2xl px-5 py-4 font-bold outline-none border-2 border-transparent focus:border-blue-500 transition"
                >
                    <option value="" disabled selected>Pilih Kategori</option>
                    {#each categories as cat}
                        <option value={cat.id}>{cat.name}</option>
                    {/each}
                </select>
                <input
                    type="number"
                    bind:value={trxForm.amount}
                    placeholder="Nominal Rp"
                    required
                    class="w-full bg-gray-100 rounded-2xl px-5 py-4 font-bold outline-none border-2 border-transparent focus:border-blue-500 transition"
                />
                <input
                    type="date"
                    bind:value={trxForm.date}
                    required
                    class="w-full bg-gray-100 rounded-2xl px-5 py-4 font-bold outline-none border-2 border-transparent focus:border-blue-500 transition"
                />
                <textarea
                    bind:value={trxForm.note}
                    placeholder="Catatan (Opsional)"
                    rows="2"
                    class="w-full bg-gray-100 rounded-2xl px-5 py-4 font-bold outline-none border-2 border-transparent focus:border-blue-500 transition"
                ></textarea>

                <div class="flex gap-3 mt-6">
                    <button
                        type="button"
                        onclick={() => (showTrxModal = false)}
                        class="flex-1 font-bold text-gray-400 cursor-pointer hover:text-gray-600"
                        >Batal</button
                    >
                    <button
                        type="submit"
                        class="flex-1 bg-gray-900 text-white py-4 rounded-2xl font-bold shadow-lg cursor-pointer hover:bg-gray-800"
                        >Simpan</button
                    >
                </div>
            </form>
        </div>
    </div>
{/if}

{#if showAddGoalModal}
    <div
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-md z-50 flex items-center justify-center p-4"
    >
        <div class="bg-white rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl">
            <h2 class="text-2xl font-black mb-6 tracking-tighter">
                Buat Target Baru 🎯
            </h2>
            <form onsubmit={submitNewGoal} class="space-y-4">
                <input
                    type="text"
                    bind:value={newGoalForm.title}
                    placeholder="Apa impian lu? (Cth: Beli Laptop)"
                    required
                    class="w-full bg-gray-100 rounded-2xl px-5 py-4 font-bold outline-none focus:border-blue-500 border-2 border-transparent transition"
                />
                <input
                    type="number"
                    bind:value={newGoalForm.target_amount}
                    placeholder="Target Rp"
                    required
                    class="w-full bg-gray-100 rounded-2xl px-5 py-4 font-bold outline-none focus:border-blue-500 border-2 border-transparent transition"
                />
                <input
                    type="date"
                    bind:value={newGoalForm.deadline}
                    class="w-full bg-gray-100 rounded-2xl px-5 py-4 font-bold outline-none focus:border-blue-500 border-2 border-transparent transition"
                />
                <div class="flex gap-3 mt-6">
                    <button
                        type="button"
                        onclick={() => (showAddGoalModal = false)}
                        class="flex-1 font-bold text-gray-400 cursor-pointer hover:text-gray-600"
                        >Batal</button
                    >
                    <button
                        type="submit"
                        class="flex-1 bg-blue-600 text-white py-4 rounded-2xl font-bold shadow-lg cursor-pointer hover:bg-blue-700"
                        >Gas Impian!</button
                    >
                </div>
            </form>
        </div>
    </div>
{/if}

{#if showGoalModal}
    <div
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-md z-50 flex items-center justify-center p-4"
    >
        <div
            class="bg-white rounded-[2.5rem] w-full max-w-sm p-8 shadow-2xl text-center"
        >
            <h2 class="text-xl font-black mb-2">
                Nabung buat {selectedGoal.title}
            </h2>
            <p class="text-sm text-gray-500 mb-6 font-medium">
                Pelan-pelan asal kelakon bro.
            </p>
            <form onsubmit={submitGoalFunds} class="space-y-4">
                <input
                    type="number"
                    bind:value={goalFundAmount}
                    placeholder="Nominal Rp"
                    required
                    class="w-full bg-gray-100 rounded-2xl px-5 py-4 font-bold text-center text-2xl outline-none border-2 border-transparent focus:border-green-500"
                />
                <div class="flex gap-3 mt-6">
                    <button
                        type="button"
                        onclick={() => (showGoalModal = false)}
                        class="flex-1 font-bold text-gray-400 cursor-pointer hover:text-gray-600"
                        >Nanti</button
                    >
                    <button
                        type="submit"
                        class="flex-1 bg-green-500 text-white py-4 rounded-2xl font-bold shadow-lg cursor-pointer hover:bg-green-600"
                        >Tambah Tabungan</button
                    >
                </div>
            </form>
        </div>
    </div>
{/if}
