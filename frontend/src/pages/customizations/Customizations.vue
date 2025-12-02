<script setup>
import { ref, watch, onMounted } from "vue"
import { userStore } from '@/stores/userStore.js' 
import axios from 'axios'

import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"

const API_URL = 'http://localhost:8000'

const DEFAULT_AVATARS = ['Felix', 'Aneka', 'Zack', 'Midnight', 'Bear'];

const currentAvatarSeed = ref(userStore.user?.custom_avatar_seed || 'default')
const activeTab = ref('wardrobe')
const isProcessing = ref(false)
const feedbackMessage = ref('') 

const myWardrobe = ref(userStore.user?.unlocked_avatars || [...DEFAULT_AVATARS])

watch(() => userStore.user, (newUser) => {
    if (newUser) {
        myWardrobe.value = (newUser.unlocked_avatars && newUser.unlocked_avatars.length > 0) 
            ? newUser.unlocked_avatars 
            : [...DEFAULT_AVATARS];
            
        currentAvatarSeed.value = newUser.custom_avatar_seed || 'default'
    }
}, { deep: true, immediate: true })

const shopAvatars = ref([
    { seed: 'King', price: 100 },
    { seed: 'Queen', price: 100 },
    { seed: 'Robot', price: 250 },
    { seed: 'Alien', price: 500 },
    { seed: 'Zombie', price: 500 },
    { seed: 'Mario', price: 1000 },
    { seed: 'Sonic', price: 1000 }
])

function showFeedback(msg) {
    feedbackMessage.value = msg
    setTimeout(() => feedbackMessage.value = '', 3000)
}

function selectAvatar(seed) {
    currentAvatarSeed.value = seed
}

async function saveAvatar() {
    if (!userStore.user?.id) return

    try {
        const payload = {
            custom_avatar_seed: currentAvatarSeed.value,
            unlocked_avatars: myWardrobe.value 
        }

        const response = await axios.put(`${API_URL}/api/users/${userStore.user.id}`, payload)
        
        userStore.login(userStore.token, response.data.data)
        showFeedback("Avatar equipped! 😎")

    } catch (error) {
        console.error(error)
        showFeedback("Error equipping avatar. ❌")
    }
}

const buyAvatar = async (item) => {
    if (userStore.user?.coins_balance < item.price) {
        showFeedback(`You need ${item.price - userStore.user.coins_balance} more coins 💰`)
        return;
    }

    if (!confirm(`Buy "${item.seed}" for ${item.price} coins?`)) return;

    isProcessing.value = true;

    try {
        const response = await axios.post(`${API_URL}/api/avatars/buy`, {
            seed: item.seed,
            price: item.price
        });

        const updatedUser = response.data.user;
        
        userStore.login(userStore.token, updatedUser);
        
        activeTab.value = 'wardrobe';
        selectAvatar(item.seed);
        showFeedback(`Success! You bought ${item.seed}. 🎉`);

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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <Card class="md:col-span-1 border-indigo-100 shadow-md h-fit sticky top-6">
            <CardHeader class="text-center pb-2">
                <CardTitle>Preview</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col items-center gap-4">
                <div class="w-48 h-48 rounded-full overflow-hidden border-4 border-indigo-500 bg-indigo-50 shadow-inner">
                    <img 
                        :src="`https://api.dicebear.com/7.x/avataaars/svg?seed=${currentAvatarSeed}`" 
                        class="w-full h-full object-cover"
                    >
                </div>
                <div class="text-center">
                    <Badge variant="secondary" class="mt-2 text-lg px-3 py-1">
                        {{ currentAvatarSeed }}
                    </Badge>
                </div>
            </CardContent>
            <CardFooter>
                <Button class="w-full bg-indigo-600 hover:bg-indigo-700" @click="saveAvatar">
                    Equip Avatar
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
                    My Wardrobe ({{ myWardrobe.length }})
                </button>
                <button 
                    @click="activeTab = 'shop'"
                    class="flex-1 py-4 text-sm font-semibold transition-colors border-b-2"
                    :class="activeTab === 'shop' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:bg-slate-50'"
                >
                    Avatar Shop ({{ shopAvatars.length }})
                </button>
            </div>

            <div v-if="activeTab === 'wardrobe'" class="p-6">
                <div class="grid grid-cols-3 sm:grid-cols-4 gap-4">
                    <div 
                        v-for="seed in myWardrobe" 
                        :key="seed"
                        @click="selectAvatar(seed)"
                        class="cursor-pointer relative rounded-xl p-1 border-2 transition-all hover:bg-slate-50"
                        :class="currentAvatarSeed === seed ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-200' : 'border-slate-100'"
                    >
                        <img 
                            :src="`https://api.dicebear.com/7.x/avataaars/svg?seed=${seed}`" 
                            class="w-full aspect-square rounded-full bg-white shadow-sm"
                        >
                        <div v-if="currentAvatarSeed === seed" class="absolute top-0 right-0 bg-indigo-600 text-white rounded-full p-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'shop'" class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4" :class="{'opacity-50 pointer-events-none': isProcessing}">
                    <div 
                        v-for="item in shopAvatars" 
                        :key="item.seed"
                        class="border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-all group bg-white flex flex-col"
                    >
                        <div class="bg-slate-50 p-4 flex justify-center items-center flex-1">
                            <img :src="`https://api.dicebear.com/7.x/avataaars/svg?seed=${item.seed}`" class="w-20 h-20 transition-transform group-hover:scale-110">
                        </div>
                        
                        <div class="p-3 border-t border-slate-100 text-center bg-white space-y-2">
                            <span class="block font-bold text-slate-800 text-sm">{{ item.seed }}</span>
                            
                            <Button 
                                size="sm" 
                                variant="outline" 
                                class="w-full text-xs font-bold"
                                :class="[
                                    myWardrobe.includes(item.seed) ? 'bg-slate-100 text-slate-500 cursor-not-allowed' :
                                    userStore.user?.coins_balance >= item.price 
                                    ? 'border-emerald-500 text-emerald-600 hover:bg-emerald-50' 
                                    : 'border-slate-200 text-slate-400 cursor-not-allowed'
                                ]"
                                :disabled="userStore.user?.coins_balance < item.price || myWardrobe.includes(item.seed)"
                                @click="buyAvatar(item)"
                            >
                                <span v-if="myWardrobe.includes(item.seed)">Owned</span>
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