<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-10 to-emerald-100 flex flex-col font-sans select-none">
    <div class="flex flex-col justify-center items-center gap-5 mt-10 p-4">
        <h1 class="text-4xl font-black text-emerald-900 mb-3 tracking-tight drop-shadow-[0_2px_4px_rgba(0,0,0,0.15)]">
          Game History
        </h1>

        <div v-if="!userStore.isLoggedIn" class="mt-10 text-center text-lg text-gray-500">
          Log in to see your game history.
        </div>

        <div v-else-if="matches.length === 0" class="mt-10 text-center text-lg text-gray-500">
          No matches found.
        </div>

        <Card v-for="match in matches" class="w-full max-w-5xl" :class="{
                'bg-blue-300': match.player1_marks > match.player2_marks,
                'bg-red-300': match.player1_marks <= match.player2_marks}">
            <CardHeader>
              <CardTitle class="text-2xl font-bold">
                <div class="grid grid-cols-[1fr_auto_1fr] items-center w-full">

                  <div class="text-left">
                    <span class="text-lg">{{ formatDate(match.began_at) }}</span>
                  </div>

                  <div class="flex flex-col items-center leading-tight">
                    <span class="text-xl font-bold whitespace-nowrap">
                      {{ match.player1_points }} - {{ match.player2_points }}
                    </span>

                    <span class="text-base opacity-80 whitespace-nowrap">
                      ({{ match.player1_marks }} - {{ match.player2_marks }})
                    </span>
                  </div>

                  <div class="flex flex-col text-right leading-tight">
                    <span class="truncate font-medium">
                      {{ match.player1_name }}
                    </span>

                    <span class="text-sm opacity-70">vs</span>

                    <span class="truncate font-medium">
                      {{ match.player2_name }}
                    </span>
                  </div>

                </div>
              </CardTitle>
            </CardHeader>
        </Card>
    </div>
  </div>
</template>

<script setup>

import { ref, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { userStore } from '@/stores/userStore.js'
import axios from 'axios'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle
} from '@/components/ui/card'

const matches = ref([])

onMounted(() => {
  getMatches()
})

const getMatches = async () => {
  if (!userStore.isLoggedIn) {
    return;
  }


  try {
    const user = userStore.user
    const response  = await axios.get(`/api/matches/${user.id}/finished`);
    matches.value = response.data.data.reverse();
  } catch (error) {
    matches.value = [];
  }
}

const formatDate = (date) => {
  const newDate = new Date(date);
  return `${String(newDate.getDate()).padStart(2, '0')}/${String(newDate.getMonth() + 1).padStart(2, '0')}/${newDate.getFullYear()}`;
}
</script>
