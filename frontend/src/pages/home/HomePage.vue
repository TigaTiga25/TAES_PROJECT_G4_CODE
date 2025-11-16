<template>
    <div class="flex flex-row justify-center items-stretch gap-5 mt-10 p-4">

        <Card class="w-full max-w-md">
            <CardHeader>
                <CardTitle class="text-3xl font-bold text-center">
                    Jogo de Bisca (TAES)
                </CardTitle>
                <CardDescription class="text-center">
                    Joga contra o Bot, vê o teu histórico e personaliza o teu jogo.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">

                <div class="flex justify-center">
                    <Button @click="playGame" size="lg" class="w-full hover:bg-purple-500 hover:text-slate-200">
                        Começar Nova Partida (vs. Bot)
                    </Button>
                </div>

               <div class="grid grid-cols-2 gap-4">
                    <Button variant="secondary">
                        Histórico de Partidas
                    </Button>
                    <Button variant="secondary">
                        Scoreboards
                    </Button>
                    <Button variant="secondary" class="col-span-2">
                        Customizações (Decks e Avatares)
                    </Button>

    
                    <div class="col-span-2 flex justify-center">
                        <button 
                            @click="handleLogout" 
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                            Sair (Logout)
                        </button>
                    </div>
                </div>


                </CardContent>
        </Card>

    </div>
</template>

<script setup>

import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { userStore } from '@/stores/userStore.js';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle
} from '@/components/ui/card'

const router = useRouter()

const handleLogout = async () => {
    
    await userStore.logout();
    
    // Redireciona para a página de login
    router.push('/'); 
};

const playGame = () => {
  router.push('/gameBoard') 
}
</script>
