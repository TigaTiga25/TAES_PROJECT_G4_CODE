<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { userStore } from '@/stores/userStore.js'
import { useRouter } from 'vue-router'

const router = useRouter()
// Inicializamos como array vazio para evitar o erro de "undefined length"
const transactions = ref([]) 
const isLoading = ref(true)
const showBuyModal = ref(false)
const isProcessing = ref(false)

// 1. PACOTES CORRETOS
const coinPackages = [
    { coins: 10, price: 1, label: 'Pacote Básico', icon: '💰' },
    { coins: 20, price: 2, label: 'Pacote Duplo', icon: '🪙' },
    { coins: 50, price: 5, label: 'Saco de Moedas', icon: '💎' },
    { coins: 100, price: 10, label: 'Baú de Tesouro', icon: '👑' }
]

// 2. FUNÇÃO DE COMPRA (Corrigida a rota para incluir /api)
const buyPackage = async (pkg) => {
    if (!confirm(`Queres comprar ${pkg.coins} moedas por ${pkg.price}€?`)) return;

    isProcessing.value = true;

    try {
        // CORREÇÃO: Adicionado '/api' antes da rota
        const response = await axios.post('/api/users/buy-coins', {
            amount: pkg.coins,
            cost: pkg.price,
            payment_type: 'MBWAY'
        })

        userStore.user.coins_balance = response.data.new_balance
        showBuyModal.value = false
        
        alert('Compra realizada com sucesso!')
        
        // Atualiza a lista após a compra
        await fetchTransactions()

    } catch (error) {
        console.error("Erro na compra:", error)
        alert('Erro ao processar compra. Verifica se tens o backend ligado.')
    } finally {
        isProcessing.value = false;
    }
}

// 3. BUSCAR HISTÓRICO 
const fetchTransactions = async () => {
    isLoading.value = true
    try {
        // CORREÇÃO: Adicionado '/api' antes da rota
        const response = await axios.get('/api/users/transactions')
        
        // CORREÇÃO: Garante que é sempre um array, mesmo se vier null
        transactions.value = response.data.data || [] 
    } catch (error) {
        console.error("Erro ao buscar transações:", error)
        transactions.value = [] // Em caso de erro, define como array vazio para não quebrar a página
    } finally {
        isLoading.value = false
    }
}

const goBack = () => router.push('/home')

onMounted(fetchTransactions)
</script>

<template>
    <div class="min-h-screen bg-slate-50 py-10 px-4">
        <div class="max-w-5xl mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <button @click="goBack" class="p-2 hover:bg-slate-200 rounded-full transition text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">My Wallet</h1>
                        <p class="text-slate-500 text-sm">Manage your balance and view history.</p>
                    </div>
                </div>

                <div class="bg-white px-6 py-3 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Current Balance</p>
                        <p class="text-3xl font-bold text-slate-800">
                            {{ userStore.user?.coins_balance ?? '---' }} 
                            <span class="text-yellow-500 text-xl">🪙</span>
                        </p>
                    </div>
                    <div class="h-10 w-px bg-slate-200 mx-2"></div>
                    <button 
                        @click="showBuyModal = true"
                        class="bg-slate-900 hover:bg-slate-800 text-white font-medium py-2 px-4 rounded-lg transition shadow-md flex items-center gap-2 text-sm"
                    >
                        <span>+</span> Add Funds
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-700">Transaction History</h3>
                    <button @click="fetchTransactions" class="text-xs text-blue-600 hover:underline">Refresh</button>
                </div>

                <div v-if="isLoading" class="p-10 text-center text-slate-400">
                    <span class="animate-pulse">Loading data...</span>
                </div>

                <div v-else-if="transactions && transactions.length === 0" class="p-10 text-center text-slate-400">
                    <span>No transactions found.</span>
                </div>

                <table v-else class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 font-medium">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Description</th>
                            <th class="px-6 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="t in transactions" :key="t.id" class="hover:bg-slate-50 transition group">
                            <td class="px-6 py-4 text-slate-500 text-xs">
                                {{ new Date(t.date).toLocaleString('en-GB') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-700">{{ t.type_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold font-mono text-base"
                                :class="t.amount > 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ t.amount > 0 ? '+' : '' }}{{ t.amount }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Transition name="fade">
                <div v-if="showBuyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showBuyModal = false"></div>

                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all">
                        
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-slate-800">Coin Store</h2>
                            <button @click="showBuyModal = false" class="text-slate-400 hover:text-slate-600 transition">
                                X
                            </button>
                        </div>

                        <div class="p-6 bg-white">
                            <p class="text-slate-500 text-sm mb-6 text-center">Select a package to add coins to your account immediately.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" :class="{'opacity-50 pointer-events-none': isProcessing}">
                                <div 
                                    v-for="pkg in coinPackages" 
                                    :key="pkg.coins"
                                    @click="buyPackage(pkg)"
                                    class="border-2 border-slate-100 rounded-xl p-4 text-center cursor-pointer transition-all duration-200 hover:border-emerald-500 hover:shadow-lg hover:-translate-y-1 group relative overflow-hidden"
                                >
                                    <div class="relative z-10">
                                        <div class="text-4xl mb-2 filter drop-shadow-sm">{{ pkg.icon }}</div>
                                        <h3 class="font-bold text-slate-800 text-lg">{{ pkg.coins }} Coins</h3>
                                        <p class="text-xs text-slate-500 mb-3">{{ pkg.label }}</p>
                                        <div class="inline-block bg-slate-900 text-white font-bold py-1 px-3 rounded-full text-sm group-hover:bg-emerald-600 transition-colors">
                                            {{ pkg.price }}€
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-3 text-center border-t border-slate-100">
                            <p class="text-xs text-slate-400">Secure Payment (Academic Simulation)</p>
                        </div>
                    </div>
                </div>
            </Transition>

        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>