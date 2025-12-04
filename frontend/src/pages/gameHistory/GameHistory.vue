<template>
  <div class="min-h-screen flex flex-col font-sans select-none">
    <div class="flex flex-col justify-center items-center gap-5 mt-10 p-4">
        <h1 class="text-4xl font-black text-emerald-900 mb-3 tracking-tight drop-shadow-[0_2px_4px_rgba(0,0,0,0.15)]">
          Game History
        </h1>

        <div v-if="!userStore.isLoggedIn" class="mt-10 text-center text-lg text-gray-500">
          Log in to see your game history.
        </div>

        <div v-else class="w-full max-w-4xl">
          <div class="bg-white rounded-lg p-4 mb-6 shadow-md border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Filters</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="flex flex-col">
                <label class="text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input
                  v-model="filterDateFrom"
                  type="date"
                  class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
              </div>

              <div class="flex flex-col">
                <label class="text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input
                  v-model="filterDateTo"
                  type="date"
                  class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
              </div>

              <div class="flex flex-col">
                <label class="text-sm font-medium text-gray-700 mb-2">Result</label>
                <select
                  v-model="filterResult"
                  class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
                  <option value="">All Results</option>
                  <option value="win">Win</option>
                  <option value="loss">Loss</option>
                </select>
              </div>

              <div class="flex flex-col">
                <label class="text-sm font-medium text-gray-700 mb-2">Achievement</label>
                <select
                  v-model="filterAchievement"
                  class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
                  <option value="">All</option>
                  <option value="capote">Capote</option>
                  <option value="bandeira">Bandeira</option>
                  <option value="either">Capote/Bandeira</option>
                </select>
              </div>
            </div>

            <button
              @click="clearFilters"
              class="mt-4 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
            >
              Clear Filters
            </button>
          </div>

          <div v-if="curatedMatches.length === 0" class="mt-10 text-center text-lg text-gray-500">
            No matches found.
          </div>

            <Card v-for="match in curatedMatches" @click="toggleMatch(match.id)" class="w-full max-w-4xl rounded-lg relative mb-4 hover:scale-105 transition-transform duration-300 ease-in-out" :class="{
                  'bg-radial from-blue-400 to-blue-500': match.player1_marks > match.player2_marks,
                  'bg-radial from-red-400 to-rose-500': match.player1_marks <= match.player2_marks,
                  'mb-8': isExpanded(match.id),
                  'mt-8': isExpanded(match.id)}">
            <CardHeader>
              <CardTitle class="text-xl font-semibold text-gray-900">
                <div class="grid grid-cols-[1fr_auto_1fr] items-center w-full p-6">

                  <div class="text-left">
                    <span class="text-sm text-gray-600">{{ formatDate(match.ended_at) }}</span>
                  </div>

                  <div class="flex flex-col items-center leading-tight">
                    <span class="text-3xl font-extrabold text-gray-800">
                      {{ match.player1_points == null ? 0 : match.player1_points }} - {{ match.player2_points == null ? 0 : match.player2_points }}
                    </span>

                    <span class="text-sm opacity-80 text-gray-600">
                      ({{ match.player1_marks }} - {{ match.player2_marks }})
                    </span>
                  </div>

                  <div class="flex flex-col text-right leading-tight">
                    <span class="truncate font-medium">
                      {{ match.player1_name }}
                    </span>

                    <span class="text-sm opacity-70 text-gray-700">vs</span>

                    <span class="truncate font-medium">
                      {{ match.player2_name == match.player1_name ? 'CPU' : match.player2_name }}
                    </span>
                  </div>

                </div>
              </CardTitle>
              <div v-show="isExpanded(match.id)" class="px-4 pb-6 pt-2 transition-all duration-300 ease-in-out">
                <Card v-for="(game, gameNumber) in gamesByMatch(match.id)" :key="game.id" class="relative w-full mb-4 max-w-4xl mt-4 border-2 border-gray-800 rounded-lg"
                :class="{
                    'bg-radial from-blue-300 to-indigo-600': game.player1_points > game.player2_points,
                    'bg-radial from-red-300 to-rose-700': game.player1_points < game.player2_points,
                    'bg-radial from-gray-300 to-gray-500': game.player1_points === game.player2_points
                  }">
                    <div class="grid grid-cols-[1fr_auto_1fr] items-center w-full p-4">
                      <div class="text-left">
                        <span class="text-sm text-gray-700">{{ formatDate(game.began_at, true) }}</span>
                      </div>

                      <div class="flex flex-col items-center justify-center leading-tight gap-1">
                        <span class="text-xl font-semibold whitespace-nowrap">
                          {{ game.player1_points == null ? 0 : game.player1_points }} - {{ game.player2_points == null ? 0 : game.player2_points }}
                        </span>
                      </div>

                      <div class="flex flex-col text-right leading-tight">
                        <span class="truncate font-medium">
                          {{ game.player1_name }}
                        </span>

                        <span class="truncate font-medium text-gray-700">
                          {{ getAccumulatedScore(gamesByMatch(match.id), gameNumber) }}
                        </span>
                      </div>
                    </div>
                    <span class="absolute left-1/2 transform -translate-x-1/2 bottom-2 text-xs text-gray-700 opacity-75">{{ formatDuration(game.total_time) }}</span>
                </Card>
              </div>
            </CardHeader>
        </Card>
        </div>
    </div>
  </div>
</template>

<script setup>

import { ref, onMounted, computed } from 'vue'
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
const games = ref([])
const filterDateFrom = ref('')
const filterDateTo = ref('')
const filterResult = ref('')
const filterAchievement = ref('')

onMounted(() => {
  getMatches()
  getGames()
})

const getMatches = async () => {
  if (!userStore.isLoggedIn) {
    return;
  }


  try {
    const user = userStore.user
    const response  = await axios.get(`/api/matches/${user.id}/finished`);
    matches.value = response.data.data;
  } catch (error) {
    matches.value = [];
  }
}

const getGames = async () => {
  if (!userStore.isLoggedIn) {
    return;
  }


  try {
    const user = userStore.user
    const response  = await axios.get(`/api/games/${user.id}/finished`);
    games.value = response.data.data;
  } catch (error) {
    games.value = [];
  }
}

const gamesByMatch = (matchId) => {
  return games.value.filter(game => game.match_id === matchId);
}

const expandedMatches = ref([])

const toggleMatch = (matchId) => {
  const index = expandedMatches.value.indexOf(matchId)
  if (index === -1) {
    expandedMatches.value.push(matchId)
  } else {
    expandedMatches.value.splice(index, 1)
  }
}

const isExpanded = (matchId) => expandedMatches.value.includes(matchId)

const formatDate = (date, showHour) => {
  const newDate = new Date(date);
  let hour = "";
  if (showHour) {
    hour = `${String(newDate.getHours()).padStart(2, '0')}:${String(newDate.getMinutes()).padStart(2, '0')}:${String(newDate.getSeconds()).padStart(2, '0')}`;
  }

  return `${String(newDate.getDate()).padStart(2, '0')}/${String(newDate.getMonth() + 1).padStart(2, '0')}/${newDate.getFullYear()} ${hour}`;
}

const formatDuration = (seconds) => {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins}m ${String(secs).padStart(2, '0')}s`;
}

const getMarks = (score) => {
  if (score >= 61 && score <= 90) return 1;
  if (score >= 91 && score <= 119) return 2;
  if (score === 120) return 4;
  return 0;
}

const getAccumulatedScore = (gamesArr, currentIndex) => {
  let p1Marks = 0;
  let p2Marks = 0;
  for (let i = currentIndex; i < gamesArr.length; i++) {
    const game = gamesArr[i];
    const p1 = game.player1_points ?? 0;
    const p2 = game.player2_points ?? 0;
    if (p1 > p2) {
      p1Marks += getMarks(p1);
    } else if (p2 > p1) {
      p2Marks += getMarks(p2);
    }
  }
  return `${p1Marks} - ${p2Marks}`;
}

const isWin = (match) => {
  return match.player1_marks > match.player2_marks;
}

const matchHasCapote = (match) => {
  const games = gamesByMatch(match.id) || [];
  return games.some(game => {
    const p1 = game.player1_points ?? 0;
    const p2 = game.player2_points ?? 0;

    return (p1 > p2 && p1 >= 91 && p1 <= 119) || (p2 > p1 && p2 >= 91 && p2 <= 119);
  });
}

const matchHasBandeira = (match) => {
  const games = gamesByMatch(match.id) || [];
  return games.some(game => {
    const p1 = game.player1_points ?? 0;
    const p2 = game.player2_points ?? 0;

    return (p1 > p2 && p1 === 120) || (p2 > p1 && p2 === 120);
  });
}


const matchesDateFilter = computed(() => {
  return matches.value.filter(match => {
    if (!filterDateFrom.value && !filterDateTo.value) {
      return true;
    }

    const matchDate = new Date(match.ended_at);
    const matchDateOnly = new Date(matchDate.getFullYear(), matchDate.getMonth(), matchDate.getDate());

    if (filterDateFrom.value) {
      const fromDate = new Date(filterDateFrom.value);
      if (matchDateOnly < fromDate) {
        return false;
      }
    }

    if (filterDateTo.value) {
      const toDate = new Date(filterDateTo.value);
      if (matchDateOnly > toDate) {
        return false;
      }
    }

    return true;
  });
})

const matchesResultFilter = computed(() => {
  return matchesDateFilter.value.filter(match => {
    if (!filterResult.value) {
      return true;
    }

    if (filterResult.value === 'win') {
      return isWin(match);
    } else if (filterResult.value === 'loss') {
      return !isWin(match);
    }

    return true;
  });
})

const matchesAchievementFilter = computed(() => {
  return matchesResultFilter.value.filter(match => {
    if (!filterAchievement.value) return true;

    const hasCapote = matchHasCapote(match);
    const hasBandeira = matchHasBandeira(match);

    if (filterAchievement.value === 'capote') return hasCapote && !hasBandeira;
    if (filterAchievement.value === 'bandeira') return hasBandeira;
    if (filterAchievement.value === 'either') return hasCapote || hasBandeira;

    return true;
  });
})

const curatedMatches = computed(() => {
  return [...matchesAchievementFilter.value].sort((a, b) => {
    const pa = (matchHasBandeira(a) ? 2 : (matchHasCapote(a) ? 1 : 0));
    const pb = (matchHasBandeira(b) ? 2 : (matchHasCapote(b) ? 1 : 0));
    if (pb !== pa) return pb - pa;
    const dateA = new Date(a.ended_at);
    const dateB = new Date(b.ended_at);
    return dateB - dateA;
  });
})

const clearFilters = () => {
  filterDateFrom.value = '';
  filterDateTo.value = '';
  filterResult.value = '';
  filterAchievement.value = '';
}
</script>
