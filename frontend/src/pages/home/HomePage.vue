<template>
  <div class="min-h-screen bg-green-10 flex flex-col">

    <!-- NAV BAR -->
    <header class="w-full bg-white/90 backdrop-blur-md border-b border-green-200 sticky top-0 z-30 shadow-md">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">

        <!-- ESQUERDA: Avatar + info se user -->
        <div v-if="user" class="flex items-center gap-4">
          <!-- AVATAR -->
          <div class="w-12 h-12 rounded-full overflow-hidden bg-green-600 text-white 
                      flex items-center justify-center shadow-lg font-bold border-2 border-green-400">
            <img
              v-if="user?.photo_avatar_filename"
              :src="`http://localhost:8000/storage/photos_avatars/${user.photo_avatar_filename}`"
              alt="Avatar"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-xl">
              {{ user.name.charAt(0).toUpperCase() }}
            </span>
          </div>

          <!-- NAME + COINS -->
          <div class="flex flex-col leading-tight">
            <span class="font-semibold text-green-900 text-lg">{{ user.name }}</span>
            <span class="text-sm text-green-700">Coins: {{ user.coins_balance }}</span>
          </div>
        </div>

        <!-- DIREITA -->
        <div class="flex items-center gap-2">
          <Button v-if="isGuest" @click="goToLogin" variant="secondary" class="bg-green-200 hover:bg-green-300 text-green-900">
            Login
          </Button>
          <button v-if="!isGuest" @click="handleLogout" class="bg-red-200 hover:bg-red-300 text-red-800 px-4 py-2 rounded-md font-medium transition shadow">
            Logout
          </button>
        </div>
      </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-grow flex flex-col items-center justify-start px-4 py-12 text-center">

      <h1 class="text-5xl font-extrabold text-green-900 mb-4 drop-shadow-lg">The Bisca</h1>
      <p class="text-green-700 text-xl mb-12">Click below to start a new game!</p>

      <div class="flex flex-wrap gap-6 justify-center mb-12">
        <Button v-if="!isGuest" @click="newMatch" size="lg" class="px-12 py-6 text-2xl font-semibold bg-green-100 hover:bg-green-200 text-green-900 shadow-md rounded-xl transition transform hover:scale-105">
          New Match
        </Button>

        <Button @click="playPracticeGame" size="lg" class="px-12 py-6 text-2xl font-semibold bg-green-100 hover:bg-green-200 text-green-900 shadow-md rounded-xl transition transform hover:scale-105">
            Practice Game
        </Button>
      </div>

      <div v-if="!isGuest" class="w-full max-w-2xl mt-10 text-left">
        <h2 class="text-2xl font-bold mb-6 text-green-900 border-b-2 border-green-300 pb-2">Unfinished Matches</h2>

        <ul class="space-y-3">
          <li
            v-for="match in unfinishedMatches"
            :key="match.id"
            class="p-5 bg-white rounded-2xl shadow-md flex justify-between items-center border border-green-200 hover:shadow-lg transition">
            <span class="font-medium text-green-800">{{ match.player1_marks }} - {{ match.player2_marks }}</span>
            <Button size="sm" @click="resumeMatch(match.id)" class="bg-green-100 hover:bg-green-200 text-green-900 px-4 py-2 rounded-lg shadow transition">
              Resume
            </Button>
          </li>
          <li v-if="unfinishedMatches.length === 0" class="text-green-600 italic">No unfinished matches.</li>
        </ul>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { userStore } from '@/stores/userStore.js'
import axios from 'axios'

const router = useRouter()
const store = userStore
const user = computed(() => store.user)
const isGuest = computed(() => store.isAnonymous)

const unfinishedMatches = ref([])

const playPracticeGame = () => router.push('/gameBoard/0')
const goToLogin = () => router.push('/')

const handleLogout = async () => {
  await store.logout()
  router.push('/')
}

const resumeMatch = async (matchId) => {
    const response = await axios.post(`/api/matches/${matchId}/game`)
    const gameId = response.data.data.id
    router.push(`/gameBoard/${gameId}`)
}

onMounted(async () => {
  if (!isGuest.value) {
    try {
      const response = await axios.get(`/api/matches/${user.value.id}/unfinished`)
      unfinishedMatches.value = response.data.data || []
    } catch (err) {
      console.error('Erro ao buscar partidas por terminar', err)
    }
  }
})

const newMatch = async () => {
  try {
    const matchResponse = await axios.post('/api/matches', {
        player1_user_id: user.value.id,
        type: 9    
    })
    const matchId = matchResponse.data.data.id
    const gameResponse = await axios.post(`/api/matches/${matchId}/game`)
    const gameId = gameResponse.data.data.id
    router.push(`/gameBoard/${gameId}`)

  } catch (error) {
    console.error('Erro ao criar partida:', error)
  }
}
</script>
