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
                </div>

                </CardContent>
        </Card>

    </div>
</template>

<script setup>

import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle
} from '@/components/ui/card'

const router = useRouter()

const playGame = () => {
  router.push('/gameBoard') 
}
</script>
