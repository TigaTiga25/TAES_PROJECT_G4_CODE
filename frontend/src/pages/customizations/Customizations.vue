<script setup>
import { ref, watch, onMounted } from "vue"
import { userStore } from '@/stores/userStore.js' // Caminho corrigido
import axios from 'axios'

import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"

const API_URL = 'http://localhost:8000/api' 

// --- ESTADOS GERAIS ---
const activeTab = ref('wardrobe') // 'wardrobe' | 'shop'
const currentCategory = ref('avatars') // 'avatars' | 'decks'
const isProcessing = ref(false)
const feedbackMessage = ref('')

// ==========================================
// LÓGICA DE AVATARES
// ==========================================
const DEFAULT_AVATARS = ['Felix', 'Aneka', 'Zack', 'Midnight', 'Bear'];

const currentAvatarSeed = ref(userStore.user?.custom_avatar_seed || 'default')
const myWardrobe = ref(userStore.user?.unlocked_avatars || [...DEFAULT_AVATARS])

const shopAvatars = ref([
    { seed: 'King', price: 100 },
    { seed: 'Queen', price: 100 },
    { seed: 'Robot', price: 250 },
    { seed: 'Alien', price: 500 },
    { seed: 'Zombie', price: 500 },
    { seed: 'Mario', price: 1000 },
    { seed: 'Sonic', price: 1000 }
])

// ==========================================
// LÓGICA DE BARALHOS (DECKS)
// ==========================================
const currentDeckFolder = ref(userStore.user?.current_deck || 'default')
// Se a lista vier vazia do backend, assume que tem o default
const myDecks = ref(userStore.user?.unlocked_decks || ['default']) 

const shopDecks = ref([
    { name: 'Classic', folder: 'default', price: 0 },
    { name: 'Minimalist', folder: 'minimalist', price: 100 },
    { name: 'Pixel', folder: 'pixel', price: 250 },
    { name: 'Animals', folder: 'animals', price: 500 },
    { name: 'Arcane', folder: 'arcane', price: 1000},
    { name: 'Chess', folder: 'chess', price: 1500},
    { name: 'Miniature', folder: 'miniature', price: 2000 },
    { name: 'Figure', folder: 'figure', price: 2500 },
    { name: 'Mario', folder: 'mario', price: 5000 },
    { name: 'Modern', folder: 'modern', price: 10000 },
])

// --- WATCHER: Mantém os dados atualizados quando o login acontece ---
watch(() => userStore.user, (newUser) => {
    if (newUser) {
        // Atualizar Avatares
        myWardrobe.value = (newUser.unlocked_avatars && newUser.unlocked_avatars.length > 0) 
            ? newUser.unlocked_avatars 
            : [...DEFAULT_AVATARS];
        currentAvatarSeed.value = newUser.custom_avatar_seed || 'default'

        // Atualizar Decks
        myDecks.value = (newUser.unlocked_decks && newUser.unlocked_decks.length > 0)
            ? newUser.unlocked_decks
            : ['default'];
        currentDeckFolder.value = newUser.current_deck || 'default'
        
        // Sincronizar LocalStorage
        localStorage.setItem('userDeck', currentDeckFolder.value)
    }
}, { deep: true, immediate: true })


// --- FUNÇÕES AUXILIARES ---
function showFeedback(msg) {
    feedbackMessage.value = msg
    setTimeout(() => feedbackMessage.value = '', 3000)
}

function selectItem(item) {
    if (currentCategory.value === 'avatars') {
        currentAvatarSeed.value = item // item é o seed (string)
    } else {
        currentDeckFolder.value = item // item é a pasta (string)
    }
}

// ==========================================
// AÇÕES (BUY & EQUIP)
// ==========================================

// --- EQUIPAR (SALVAR) ---
async function saveSelection() {
    if (!userStore.user?.id) return

    try {
        let payload = {}
        let endpoint = ''

        if (currentCategory.value === 'avatars') {
            // Lógica para Avatar
            endpoint = `/users/${userStore.user.id}`
            payload = {
                custom_avatar_seed: currentAvatarSeed.value,
                unlocked_avatars: myWardrobe.value 
            }
            
            const response = await axios.put(`${API_URL}${endpoint}`, payload)
            
            // Atualiza Store Global
            if (userStore.login) userStore.login(userStore.token, response.data.data)
            
            showFeedback("Avatar equipped! 😎")

        } else {
            // Lógica para Deck
            endpoint = '/users/update-deck'
            payload = { deck: currentDeckFolder.value }
            
            const response = await axios.patch(`${API_URL}${endpoint}`, payload)
            
            // Atualizar Store Manualmente
            if(userStore.user) userStore.user.current_deck = currentDeckFolder.value
            
            // Atualizar LocalStorage
            localStorage.setItem('userDeck', currentDeckFolder.value)
            
            showFeedback("Deck equipped! 🃏")
        }

    } catch (error) {
        console.error(error)
        showFeedback("Error saving changes. ❌")
    }
}

// --- COMPRAR ---
const buyItem = async (item) => {
    // Verificar Saldo
    if (userStore.user?.coins_balance < item.price) {
        showFeedback(`Need ${item.price - userStore.user.coins_balance} more coins 💰`)
        return;
    }

    const itemName = item.seed || item.name
    if (!confirm(`Buy "${itemName}" for ${item.price} coins?`)) return;

    isProcessing.value = true;

    try {
        let endpoint = ''
        let payload = {}

        if (currentCategory.value === 'avatars') {
            endpoint = '/avatars/buy'
            payload = { seed: item.seed, price: item.price }
        } else {
            endpoint = '/users/buy-deck'
            payload = { deck: item.folder, price: item.price }
        }

        const response = await axios.post(`${API_URL}${endpoint}`, payload);

        // A resposta do backend traz o user atualizado
        const updatedUser = response.data.user || response.data.data;
        
        // Atualiza Store Global
        if (userStore.login) userStore.login(userStore.token, updatedUser);
        
        activeTab.value = 'wardrobe';
        
        // Seleciona automaticamente o item comprado
        if (currentCategory.value === 'avatars') selectItem(item.seed)
        else selectItem(item.folder)

        showFeedback(`Success! Bought ${itemName}. 🎉`);

    } catch (error) {
        console.error("Purchase error:", error);
        const msg = error.response?.data?.message || 'Error processing purchase ❌';
        showFeedback(msg);
    } finally {
        isProcessing.value = false;
    }
}
</script>

<template>
  <div class="max-w-5xl mx-auto p-6 space-y-8">
    
    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold">Customizations</h1>
            <p class="text-slate-500">Spend your coins to look unique.</p>
        </div>
        <div v-if="feedbackMessage" class="bg-slate-800 text-white px-4 py-2 rounded-lg animate-pulse shadow-lg">
            {{ feedbackMessage }}
        </div>
    </div>

    <div class="flex gap-4 justify-center bg-slate-100 p-2 rounded-xl w-fit mx-auto">
        <button 
            @click="currentCategory = 'avatars'"
            class="px-6 py-2 rounded-lg font-bold transition-all"
            :class="currentCategory === 'avatars' ? 'bg-white shadow text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
        >
            👤 Avatars
        </button>
        <button 
            @click="currentCategory = 'decks'"
            class="px-6 py-2 rounded-lg font-bold transition-all"
            :class="currentCategory === 'decks' ? 'bg-white shadow text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
        >
            🃏 Decks
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <Card class="md:col-span-1 border-indigo-100 shadow-md h-fit sticky top-6">
            <CardHeader class="text-center pb-2">
                <CardTitle>Preview</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col items-center gap-4">
                
                <div v-if="currentCategory === 'avatars'" class="w-48 h-48 rounded-full overflow-hidden border-4 border-indigo-500 bg-indigo-50 shadow-inner">
                    <img 
                        :src="`https://api.dicebear.com/7.x/avataaars/svg?seed=${currentAvatarSeed}`" 
                        class="w-full h-full object-cover"
                    >
                </div>

                <div v-if="currentCategory === 'decks'" class="w-32 h-48 rounded-lg overflow-hidden border-4 border-indigo-500 bg-white shadow-lg flex items-center justify-center">
                    <img 
                        :src="`/decks/${currentDeckFolder}/c1.png`" 
                        class="w-full h-full object-contain"
                        alt="Deck Preview"
                        @error="$event.target.src = `/decks/${currentDeckFolder}/semFace.png`" 
                    >
                </div>

                <div class="text-center">
                    <Badge variant="secondary" class="mt-2 text-lg px-3 py-1 capitalize">
                        {{ currentCategory === 'avatars' ? currentAvatarSeed : currentDeckFolder }}
                    </Badge>
                </div>
            </CardContent>
            <CardFooter>
                <Button class="w-full bg-indigo-600 hover:bg-indigo-700" @click="saveSelection">
                    Equip {{ currentCategory === 'avatars' ? 'Avatar' : 'Deck' }}
                </Button>
            </CardFooter>
        </Card>

        <Card class="md:col-span-2 min-h-[500px] flex flex-col">
            
            <div class="flex border-b border-slate-100">
                <button 
                    @click="activeTab = 'wardrobe'"
                    class="flex-1 py-4 text-sm font-semibold transition-colors border-b-2"
                    :class="activeTab === 'wardrobe' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:bg-slate-50'"
                >
                    My Collection ({{ currentCategory === 'avatars' ? myWardrobe.length : myDecks.length }})
                </button>
                <button 
                    @click="activeTab = 'shop'"
                    class="flex-1 py-4 text-sm font-semibold transition-colors border-b-2"
                    :class="activeTab === 'shop' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:bg-slate-50'"
                >
                    Shop ({{ currentCategory === 'avatars' ? shopAvatars.length : shopDecks.length }})
                </button>
            </div>

            <div v-if="activeTab === 'wardrobe'" class="p-6">
                <div class="grid grid-cols-3 sm:grid-cols-4 gap-4">
                    
                    <template v-if="currentCategory === 'avatars'">
                        <div 
                            v-for="seed in myWardrobe" 
                            :key="seed"
                            @click="selectItem(seed)"
                            class="cursor-pointer relative rounded-xl p-1 border-2 transition-all hover:bg-slate-50"
                            :class="currentAvatarSeed === seed ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-200' : 'border-slate-100'"
                        >
                            <img :src="`https://api.dicebear.com/7.x/avataaars/svg?seed=${seed}`" class="w-full aspect-square rounded-full bg-white shadow-sm">
                            <div v-if="currentAvatarSeed === seed" class="absolute top-0 right-0 bg-indigo-600 text-white rounded-full p-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </div>
                    </template>

                    <template v-if="currentCategory === 'decks'">
                        <div 
                            v-for="deckFolder in myDecks" 
                            :key="deckFolder"
                            @click="selectItem(deckFolder)"
                            class="cursor-pointer relative rounded-xl p-2 border-2 transition-all hover:bg-slate-50 flex flex-col items-center"
                            :class="currentDeckFolder === deckFolder ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-200' : 'border-slate-100'"
                        >
                            <img 
                                :src="`/decks/${deckFolder}/c1.png`" 
                                class="w-16 h-20 object-contain shadow-sm mb-2"
                                @error="$event.target.src = `/decks/${deckFolder}/semFace.png`"
                            >
                            <span class="text-xs font-bold text-slate-700 capitalize">{{ deckFolder }}</span>
                            
                            <div v-if="currentDeckFolder === deckFolder" class="absolute top-0 right-0 bg-indigo-600 text-white rounded-full p-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </div>
                    </template>

                </div>
            </div>

            <div v-if="activeTab === 'shop'" class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4" :class="{'opacity-50 pointer-events-none': isProcessing}">
                    
                    <div 
                        v-for="item in (currentCategory === 'avatars' ? shopAvatars : shopDecks)" 
                        :key="item.seed || item.folder"
                        class="border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-all group bg-white flex flex-col"
                    >
                        <div class="bg-slate-50 p-4 flex justify-center items-center flex-1">
                            <img v-if="currentCategory === 'avatars'" :src="`https://api.dicebear.com/7.x/avataaars/svg?seed=${item.seed}`" class="w-20 h-20 transition-transform group-hover:scale-110">
                            <img v-else :src="`/decks/${item.folder}/c1.png`" class="w-16 h-24 object-contain transition-transform group-hover:scale-110 shadow-md">
                        </div>
                        
                        <div class="p-3 border-t border-slate-100 text-center bg-white space-y-2">
                            <span class="block font-bold text-slate-800 text-sm capitalize">{{ item.seed || item.name }}</span>
                            
                            <Button 
                                size="sm" 
                                variant="outline" 
                                class="w-full text-xs font-bold"
                                :class="[
                                    (currentCategory === 'avatars' ? myWardrobe.includes(item.seed) : myDecks.includes(item.folder)) 
                                    ? 'bg-slate-100 text-slate-500 cursor-not-allowed' :
                                    userStore.user?.coins_balance >= item.price 
                                    ? 'border-emerald-500 text-emerald-600 hover:bg-emerald-50' 
                                    : 'border-slate-200 text-slate-400 cursor-not-allowed'
                                ]"
                                :disabled="userStore.user?.coins_balance < item.price || (currentCategory === 'avatars' ? myWardrobe.includes(item.seed) : myDecks.includes(item.folder))"
                                @click="buyItem(item)"
                            >
                                <span v-if="(currentCategory === 'avatars' ? myWardrobe.includes(item.seed) : myDecks.includes(item.folder))">Owned</span>
                                <span v-else-if="userStore.user?.coins_balance >= item.price">Buy {{ item.price }} 💰</span>
                                <span v-else>Need {{ item.price }} 💰</span>
                            </Button>
                        </div>
                    </div>

                </div>
            </div>

        </Card>
    </div>
  </div>
</template>