<script setup>
import { ref, onMounted, computed } from 'vue'
import { userStore } from '@/stores/userStore.js'
import { useRouter } from 'vue-router'

const router = useRouter()
const transactions = ref([])
const isLoading = ref(true)
const showBuyModal = ref(false)
const isProcessing = ref(false)
const filterType = ref('ALL') // Opções: 'ALL', 'CREDIT', 'DEBIT'

// --- LÓGICA DE FILTRAGEM (Ganhos vs Gastos) ---
const filteredTransactions = computed(() => {
    if (filterType.value === 'ALL') return transactions.value

    return transactions.value.filter(t => {
        // Se coins > 0 é Crédito (Ganhou), se < 0 é Débito (Gastou)
        if (filterType.value === 'CREDIT') return t.coins > 0
        if (filterType.value === 'DEBIT') return t.coins < 0
        return true
    })
})

// --- LÓGICA DE PAGINAÇÃO ---
const currentPage = ref(1)
const itemsPerPage = ref(7)

const totalPages = computed(() => {
    const total = Math.ceil(filteredTransactions.value.length / itemsPerPage.value)
    return total > 0 ? total : 1
})

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return filteredTransactions.value.slice(start, end)
})

const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++
}

const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--
}

// Reseta a página quando muda o filtro
const setFilter = (type) => {
    filterType.value = type
    currentPage.value = 1
}

// --- PACOTES DE MOEDAS ---
const coinPackages = [
    { coins: 10, price: 1, label: 'Starter Pack', icon: '💰' },
    { coins: 50, price: 4.50, label: 'Gamer Pack', icon: '💎' },
    { coins: 100, price: 9, label: 'Pro Pack', icon: '👑' },
    { coins: 500, price: 40, label: 'Whale Pack', icon: '🚀' }
]

// --- HELPER: Descrições e Ícones ---
const getTransactionDescription = (t) => {
    // Lê o campo JSON 'custom' para dar detalhes
    if (t.custom) {
        if (t.custom.item_name) return `Bought Item: ${t.custom.item_name}`
        if (t.custom.deck_id) return `Bought Deck: ${t.custom.deck_id}`
        if (t.custom.avatar_id) return `Bought Avatar: ${t.custom.avatar_id}`
        if (t.custom.source === 'welcome_bonus') return 'Welcome Bonus 🎉'
        if (t.custom.source === 'store_purchase') return 'Coin Store Purchase'
    }
    // Fallback para o nome do Tipo
    return t.type?.name || 'Transaction'
}

const getTypeIcon = (t) => {
    if (t.coins > 0) {
        // Ícones para Ganhos
        if (t.type?.name?.includes('Win') || t.type?.name?.includes('payout')) return '🎮'
        if (t.type?.name?.includes('Bonus')) return '🎁'
        return '💰'
    } else {
        // Ícones para Gastos
        if (t.type?.name?.includes('fee') || t.type?.name?.includes('stake')) return '🎫'
        return '🛒'
    }
}

// --- AÇÕES ---
const buyPackage = async (pkg) => {
    if (!confirm(`Comprar ${pkg.coins} moedas por ${pkg.price}€?`)) return;

    isProcessing.value = true;
    try {
        // Chama a userStore para ir à rota /api/shop/buy-coins
        await userStore.buyCoins(pkg.price, 'MBWAY', '919999999')

        showBuyModal.value = false
        // Atualiza a lista
        await fetchTransactions()
        alert('Compra realizada com sucesso!')
    } catch (error) {
        alert(error.response?.data?.message || 'Erro na compra.')
    } finally {
        isProcessing.value = false;
    }
}

const fetchTransactions = async () => {
    isLoading.value = true
    try {
        // Chama a userStore para ir à rota /api/transactions
        const data = await userStore.fetchTransactions()
        transactions.value = data || []
        currentPage.value = 1
    } catch (error) {
        console.error("Erro:", error)
    } finally {
        isLoading.value = false
    }
}

onMounted(fetchTransactions)
</script>

<template>
    <div class="min-h-screen bg-slate-50 py-10 px-4 font-sans">
        <div class="max-w-4xl mx-auto space-y-6">

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-4">
                    <button @click="router.push('/home')" class="p-2 hover:bg-slate-100 rounded-full transition text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">My Wallet</h1>
                        <p class="text-slate-500 text-sm">Manage transactions & balance</p>
                    </div>
                </div>

                <div class="flex items-center gap-6 bg-slate-50 px-6 py-3 rounded-xl border border-slate-100">
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-400 uppercase">Balance</p>
                        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">
                            {{ userStore.user?.coins_balance ?? 0 }} <span class="text-yellow-500">🪙</span>
                        </p>
                    </div>
                    <div class="h-10 w-px bg-slate-200"></div>
                    <button
                        @click="showBuyModal = true"
                        class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-5 rounded-lg transition-all shadow-md active:scale-95 flex items-center gap-2"
                    >
                        <span>+</span> Add Coins
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col min-h-[500px]">

                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h3 class="font-bold text-slate-700 text-lg">History</h3>

                    <div class="flex bg-slate-100 p-1 rounded-lg">
                        <button
                            @click="setFilter('ALL')"
                            :class="filterType === 'ALL' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="px-4 py-1.5 rounded-md text-sm font-medium transition-all"
                        >All</button>
                        <button
                            @click="setFilter('CREDIT')"
                            :class="filterType === 'CREDIT' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="px-4 py-1.5 rounded-md text-sm font-medium transition-all"
                        >Earned</button>
                        <button
                            @click="setFilter('DEBIT')"
                            :class="filterType === 'DEBIT' ? 'bg-white text-red-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="px-4 py-1.5 rounded-md text-sm font-medium transition-all"
                        >Spent</button>
                    </div>
                </div>

                <div v-if="isLoading" class="flex-1 flex flex-col items-center justify-center text-slate-400 py-20">
                    <span class="animate-pulse">Loading transactions...</span>
                </div>

                <div v-else-if="filteredTransactions.length === 0" class="flex-1 flex flex-col items-center justify-center text-slate-400 py-20">
                    <span class="text-4xl mb-2">📜</span>
                    <span>No transactions found.</span>
                </div>

                <div v-else class="flex-1 overflow-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/50 text-slate-400 text-xs font-bold uppercase tracking-wider sticky top-0">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Type</th>
                                <th class="px-6 py-4 font-semibold w-full">Description</th>
                                <th class="px-6 py-4 font-semibold text-right">Amount</th>
                                <th class="px-6 py-4 font-semibold text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="t in paginatedTransactions" :key="t.id" class="hover:bg-slate-50 transition duration-150 group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-2xl" :title="t.type?.name">{{ getTypeIcon(t) }}</span>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-700">{{ getTransactionDescription(t) }}</p>
                                    <p class="text-xs text-slate-400">{{ t.type?.name }}</p>
                                </td>

                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <span
                                        class="inline-block px-2.5 py-1 rounded-lg text-sm font-bold font-mono"
                                        :class="t.coins > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-50 text-red-600'"
                                    >
                                        {{ t.coins > 0 ? '+' : '' }}{{ t.coins }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right text-xs text-slate-400 whitespace-nowrap">
                                    {{ new Date(t.transaction_datetime).toLocaleDateString() }} <br>
                                    {{ new Date(t.transaction_datetime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
                    <span class="text-xs text-slate-500">Page {{ currentPage }} of {{ totalPages }}</span>
                    <div class="flex gap-2">
                        <button @click="prevPage" :disabled="currentPage === 1" class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 disabled:opacity-50 transition">Previous</button>
                        <button @click="nextPage" :disabled="currentPage === totalPages" class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 disabled:opacity-50 transition">Next</button>
                    </div>
                </div>
            </div>

            <Transition name="fade">
                <div v-if="showBuyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="showBuyModal = false"></div>

                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden transform transition-all scale-100">
                        <div class="bg-linear-to-r from-slate-900 to-slate-800 px-6 py-4 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-white flex items-center gap-2">🛒 Add Funds</h2>
                            <button @click="showBuyModal = false" class="text-slate-400 hover:text-white transition text-xl">&times;</button>
                        </div>

                        <div class="p-8">
                            <div class="grid grid-cols-2 gap-4">
                                <button
                                    v-for="pkg in coinPackages"
                                    :key="pkg.coins"
                                    @click="buyPackage(pkg)"
                                    :disabled="isProcessing"
                                    class="border border-slate-200 rounded-xl p-4 text-center hover:border-emerald-500 hover:bg-emerald-50 hover:shadow-md transition-all group disabled:opacity-50"
                                >
                                    <div class="text-3xl mb-1 group-hover:scale-110 transition-transform">{{ pkg.icon }}</div>
                                    <div class="font-bold text-slate-800">{{ pkg.coins }} Coins</div>
                                    <div class="text-xs text-slate-500 mt-1 bg-slate-100 rounded-full py-1 px-2 inline-block font-semibold group-hover:bg-emerald-100 group-hover:text-emerald-700">
                                        {{ pkg.price.toFixed(2) }}€
                                    </div>
                                </button>
                            </div>
                            <p class="text-xs text-center text-slate-400 mt-6 flex items-center justify-center gap-1">
                                Secure Transaction (Mock)
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>

        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
