<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-10 to-emerald-100 flex flex-col font-sans select-none">

    <main class="flex-grow flex flex-col items-center justify-start px-4 py-16 text-center">

      <h1 class="text-6xl font-black text-emerald-900 mb-3 tracking-tight drop-shadow-[0_2px_4px_rgba(0,0,0,0.15)]">
        The Bisca
      </h1>
      <p class="text-emerald-700 text-xl mb-14 opacity-90">Start a new match and test your skills!</p>

      <!-- BUTTONS -->
      <div class="flex flex-wrap gap-8 justify-center mb-14">
        <Button
          v-if="!isGuest"
          @click="newMatch"
          size="lg"
          class="px-12 py-6 text-2xl font-semibold bg-white hover:bg-emerald-50 text-emerald-900 rounded-2xl shadow-md border border-emerald-200 transition transform hover:-translate-y-1 hover:shadow-xl"
        >New Match</Button>

        <Button
          @click="playPracticeGame"
          size="lg"
          class="px-12 py-6 text-2xl font-semibold bg-white hover:bg-emerald-50 text-emerald-900 rounded-2xl shadow-md border border-emerald-200 transition transform hover:-translate-y-1 hover:shadow-xl"
        >Practice Game</Button>
      </div>

      <!-- UNFINISHED MATCHES -->
      <div v-if="!isGuest" class="w-full max-w-3xl mt-6 text-left">
        <h2 class="text-3xl font-bold mb-6 text-emerald-900 border-b border-emerald-300 pb-3 tracking-tight">Unfinished Matches</h2>

        <ul class="space-y-4">
          <li
            v-for="match in unfinishedMatches"
            :key="match.id"
            class="p-5 bg-white rounded-2xl shadow-sm flex justify-between items-center border border-emerald-200 hover:shadow-md hover:bg-emerald-50/40 transition"
          >
            <span class="font-medium text-emerald-900 text-lg tracking-tight">
              {{ match.player1_marks }} - {{ match.player2_marks }}
            </span>

            <div class="flex gap-3">
              <Button size="sm" @click="resumeMatch(match.id)" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-900 px-4 py-2 rounded-xl shadow-sm transition">
                Resume
              </Button>

              <Button size="sm" @click="giveUpMatch(match.id)" class="bg-red-100 hover:bg-red-200 text-red-900 px-4 py-2 rounded-xl shadow-sm transition">
                Give up
              </Button>
            </div>
          </li>

          <li v-if="unfinishedMatches.length === 0" class="text-emerald-700 italic opacity-80 text-center pt-4">
            No unfinished matches.
          </li>
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
import { toast } from 'vue-sonner'

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

const giveUpMatch = async (matchId) => {
  try {
    await axios.put(`/api/matches/${matchId}/interrupt`)
    unfinishedMatches.value = unfinishedMatches.value.filter(match => match.id !== matchId)
  } catch (error) {
    console.error('Erro ao desistir da partida:', error)
  }
}

onMounted(async () => {
  if (!isGuest.value) {
    try {
      const respUser = await axios.get('/api/auth/me')
      store.updateUser(respUser.data)
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

    if(matchResponse.data.status == 400){
      toast.error('ERROR: Insufficient balance')
      return
    }
    const matchId = matchResponse.data.data.id
    const gameResponse = await axios.post(`/api/matches/${matchId}/game`)
    const gameId = gameResponse.data.data.id
    router.push(`/gameBoard/${gameId}`)
  } catch (error) {
    console.error('Erro ao criar partida:', error)
  }
}
</script>
