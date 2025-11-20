<template>
  <div class="game-board flex flex-col items-center justify-between min-h-screen bg-green-700 p-4 relative">

    <div v-if="isGameOver" class="fixed inset-0 bg-green-700 bg-opacity-90 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-2xl text-center max-w-sm animate-fade-in border-4 border-yellow-400">
            <h2 class="text-3xl font-bold mb-4 text-gray-800">FIM DO JOGO</h2>
            <p class="text-xl mb-6 text-gray-600">Vencedor: <br><span class="font-extrabold text-blue-600 text-2xl block mt-2">{{ matchWinner }}</span></p>
            <div class="space-y-3 bg-gray-100 p-4 rounded-md">
                <div class="flex justify-between text-gray-700"><span>Jogador:</span><span class="font-bold">{{ playerPoints }}</span></div>
                <div class="flex justify-between text-gray-700"><span>Bot:</span><span class="font-bold">{{ botPoints }}</span></div>
            </div>
            <div class="flex flex-col mt-6 gap-3">
                <button @click="restartGame" class="btn bg-green-600 hover:bg-green-700 text-white font-bold">Novo Jogo</button>
                <button @click="quitGame" class="btn bg-gray-500 hover:bg-gray-600 text-white font-bold">Sair</button>
            </div>
        </div>
    </div>

    <div class="w-full max-w-5xl flex justify-between items-center mb-4">
        <h1 v-if="props.id !== 0" class="text-4xl font-bold text-white drop-shadow-md">Bisca Clássica</h1>
        <h1 v-if="props.id === 0" class="text-4xl font-bold text-white drop-shadow-md">Practice Game</h1>
        <div class="text-right text-white p-3 bg-gray-900 bg-opacity-60 rounded-lg shadow-sm border border-white/10">
            <p class="text-xs uppercase tracking-wider opacity-80">Bot</p>
            <p class="text-3xl font-mono font-extrabold text-yellow-400">{{ botPoints }}</p>
        </div>
    </div>

    <div class="bot-hand flex justify-center mb-8">
      <BotHand :cards="botCards" />
    </div>

    <div class="table-row flex items-center justify-between w-full max-w-5xl mb-8">
      <div class="deck-area flex items-center">
        <div class="deck-and-trump flex items-end gap-6">

          <div class="deck-stack flex flex-col items-center relative">
             <div v-if="deck.length > 0" class="relative">
                <div v-if="deck.length > 20" class="absolute top-[-6px] left-[-3px] w-28 h-40 bg-blue-900 rounded-lg border border-white/40"></div>
                <div v-if="deck.length > 10" class="absolute top-[-4px] left-[-2px] w-28 h-40 bg-blue-800 rounded-lg border border-white/60"></div>
                <div v-if="deck.length > 2"  class="absolute top-[-2px] left-[-1px] w-28 h-40 bg-blue-700 rounded-lg border border-white/80"></div>

                <Card :hidden="true" class="w-28 h-40 relative z-10 shadow-lg" />
             </div>

             <div v-else class="w-28 h-40 border-2 border-dashed border-white/30 rounded-lg flex items-center justify-center">
                <span class="text-white/30 text-xs">Vazio</span>
             </div>

             <span class="deck-label text-white mt-2 font-medium text-sm bg-black/30 px-2 py-0.5 rounded-full">{{ deck.length }} cartas</span>
          </div>

          <div class="trump-field flex flex-col items-center">
            <div v-if="trumpCard" class="relative">
                 <Card :card="trumpCard" class="w-28 h-40 shadow-lg" :class="{'opacity-50': deck.length === 0}" />
                 <span class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-white mt-2 font-bold text-sm uppercase tracking-widest">Trunfo</span>
            </div>
          </div>
        </div>
      </div>

      <div class="played-area flex gap-8 justify-center min-w-[260px] items-center">
        <Transition name="card-play"><div v-if="playedCards.bot" class="played bot transform rotate-[-2deg]"><Card :card="playedCards.bot" class="w-28 h-40 shadow-2xl" /><span class="block text-center text-white/80 text-xs mt-2">Bot</span></div></Transition>
        <Transition name="card-play"><div v-if="playedCards.player" class="played player transform rotate-[2deg]"><Card :card="playedCards.player" class="w-28 h-40 shadow-2xl" /><span class="block text-center text-white/80 text-xs mt-2">Tu</span></div></Transition>
      </div>
    </div>

    <div class="player-hand flex justify-center mb-8 relative">
       <div v-if="iniciateTrick === 'p' && !playedCards.player" class="absolute -top-12 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-black px-4 py-1 rounded-full font-bold text-sm shadow-lg animate-bounce">A tua vez!</div>
      <PlayerHand :cards="playerCards" @play-card="handlePlayerPlay" class="w-28 h-40" />
    </div>

    <div class="w-full max-w-5xl flex justify-between items-center mt-4">
        <div class="text-left text-white p-3 bg-gray-900 bg-opacity-60 rounded-lg shadow-sm border border-white/10">
            <p class="text-xs uppercase tracking-wider opacity-80">Jogador</p>
            <p class="text-2xl font-mono font-extrabold text-green-400">{{ playerPoints }}</p>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import BotHand from './BotHand.vue'
import PlayerHand from './PlayerHand.vue'
import Card from './Card.vue'
import generateDeck from '@/lib/generateDeck.js'
import { toast } from 'vue-sonner'
import { useRouter } from 'vue-router'

const router = useRouter()
const props = defineProps({
  id: {
    type: Number,
    required: true,
  }
});
console.log('Game ID:', props.id);
const fullDeck = generateDeck()




// --- ESTADO ---
const deck = ref([])
const playerCards = ref([])
const botCards = ref([])
const trumpCard = ref(null)
const playedCards = ref({ player: null, bot: null })
const playedCardsHistory = ref([])
const suitTracker = ref({})
const iniciateTrick = ref('p')
const canDraw = ref(true)
const playerPoints = ref(0)
const botPoints = ref(0)
const isGameOver = ref(false)
const matchWinner = ref('')

// --- REGRAS ---
const cardRankOrder = ['2', '3', '4', '5', '6', '12', '11', '13', '7', '1']
const cardPoints = { '1': 11, '7': 10, '13': 4, '11': 3, '12': 2, '6': 0, '5': 0, '4': 0, '3': 0, '2': 0 }
const allSuits = ['e', 'o', 'p', 'c'];
const suitOrder = ['e', 'o', 'p', 'c'];

// --- HELPERS ---
function getCardRank(card) { return card ? cardRankOrder.indexOf(card.value) : -1 }
function getCardPoints(card) { return card ? (cardPoints[card.value] || 0) : 0 }
function sortCardsByRank(cards) { return [...cards].sort((a, b) => getCardRank(a) - getCardRank(b)) } // Crescente
function isBigger(val1, val2) { return cardRankOrder.indexOf(val1) > cardRankOrder.indexOf(val2) }

// --- MEMÓRIA ---
function initTracker() {
  allSuits.forEach(s => { suitTracker.value[s] = { count: 0, remainingHigh: ['1', '7', '13', '11', '12'] }; });
}
function updateTracker(card) {
  const s = card.suit;
  const v = card.value;
  if (!suitTracker.value[s]) return;
  suitTracker.value[s].count++;
  suitTracker.value[s].remainingHigh = suitTracker.value[s].remainingHigh.filter(r => r !== v);
}
function isMasterCard(card) {
    const tracker = suitTracker.value[card.suit];
    if (!tracker) return false;
    const remainingInGame = tracker.remainingHigh.filter(val =>
        !botCards.value.some(c => c.suit === card.suit && c.value === val)
    );
    if (remainingInGame.length === 0) return true;
    const myRank = getCardRank(card);
    const highestRankOut = Math.max(...remainingInGame.map(v => cardRankOrder.indexOf(v)));
    return myRank > highestRankOut;
}

// ===========================================================
// HEURÍSTICA DO BOT
// ===========================================================
function playBotCard() {
  if (botCards.value.length === 0) return;

  const trumpSuit = trumpCard.value.suit;
  const sortedHand = sortCardsByRank(botCards.value);
  let chosen = null;

  // ---------------------------------------------------------
  // CENÁRIO 1: BOT ABRE O JOGO (LIDERANÇA)
  // ---------------------------------------------------------
  if (iniciateTrick.value === 'b') {
    if (canDraw.value) {
        // --- FASE DE COMPRA ---
        const nonTrumps = sortedHand.filter(c => c.suit !== trumpSuit);
        const trumps = sortedHand.filter(c => c.suit === trumpSuit);

        const trash = nonTrumps.filter(c => getCardPoints(c) === 0);
        if (trash.length > 0) chosen = trash[0];
        else if (nonTrumps.length > 0) chosen = nonTrumps[0];
        else chosen = trumps[0];

    } else {
        // --- FASE FINAL ---
        const masters = sortedHand.filter(c => isMasterCard(c) && c.suit !== trumpSuit);
        if (masters.length > 0) chosen = masters[masters.length - 1];
        else {
            const masterTrumps = sortedHand.filter(c => isMasterCard(c) && c.suit === trumpSuit);
            if (masterTrumps.length > 0) chosen = masterTrumps[masterTrumps.length - 1];
            else chosen = sortedHand[0];
        }
    }
  }
  // ---------------------------------------------------------
  // CENÁRIO 2: BOT RESPONDE (REAÇÃO)
  // ---------------------------------------------------------
  else {
    const playerCard = playedCards.value.player;
    const pSuit = playerCard.suit;
    const pPoints = getCardPoints(playerCard);
    const sameSuit = sortedHand.filter(c => c.suit === pSuit);
    const trumps = sortedHand.filter(c => c.suit === trumpSuit);

    const winningCard = sameSuit.find(c => isBigger(c.value, playerCard.value));

    const forceCut = canDraw.value && pPoints >= 10 && !winningCard && trumps.length > 0;

    if (forceCut) {
        chosen = trumps[0];
    }
    else if (sameSuit.length > 0) {
        if (winningCard) chosen = winningCard;
        else chosen = sameSuit[0];
    }
    else {
        if (trumps.length > 0) {
            if (!canDraw.value) chosen = trumps[0];
            else {
                if (pPoints >= 4) chosen = trumps[0];
                else {
                    const discard = sortedHand.filter(c => c.suit !== trumpSuit);
                    chosen = discard.length > 0 ? discard[0] : trumps[0];
                }
            }
        } else {
            chosen = sortedHand[0];
        }
    }
  }

  if (!chosen) chosen = sortedHand[0];

  playedCards.value.bot = chosen;
  botCards.value = botCards.value.filter(c => c !== chosen);

  if (iniciateTrick.value === 'p') {
    setTimeout(() => {
      trickWinner();
      clearTable();
    }, 1000);
  }
}

// ===========================================================
// RESTANTE LÓGICA
// ===========================================================

function sortPlayerHand() {
  playerCards.value.sort((a, b) => {
    const suitA = suitOrder.indexOf(a.suit);
    const suitB = suitOrder.indexOf(b.suit);
    if (suitA !== suitB) return suitA - suitB;
    return getCardRank(b) - getCardRank(a);
  });
}

function startGame() {
  const newDeck = fullDeck;
  const shuffled = newDeck.sort(() => Math.random() - 0.5)

  playerCards.value = shuffled.slice(0, 9)
  botCards.value = shuffled.slice(9, 18)
  trumpCard.value = shuffled[39]
  deck.value = shuffled.slice(18, 39)

  playedCards.value = { player: null, bot: null }
  playedCardsHistory.value = []
  canDraw.value = true

  initTracker();
  iniciateTrick.value = Math.random() < 0.5 ? 'p' : 'b'

  playerPoints.value = 0
  botPoints.value = 0
  isGameOver.value = false

  sortPlayerHand()

  if (iniciateTrick.value === 'b') {
    setTimeout(() => playBotCard(), 1000)
  }
}

function handlePlayerPlay(card) {
  if (iniciateTrick.value === 'b' && playedCards.value.bot && !canDraw.value) {
    const botSuit = playedCards.value.bot.suit
    const hasSuit = playerCards.value.some(c => c.suit === botSuit)
    if (hasSuit && card.suit !== botSuit) {
      toast.error('Tens de assistir ao naipe jogado!')
      return
    }
  }

  if ((iniciateTrick.value === 'b' && !playedCards.value.bot) || (playedCards.value.player)) {
    return
  }

  playedCards.value.player = card
  playerCards.value = playerCards.value.filter(c => c !== card)

  if (iniciateTrick.value === 'p') {
    setTimeout(() => playBotCard(), 500)
  } else {
    setTimeout(() => {
      trickWinner()
      clearTable()
    }, 1000)
  }
}

function trickWinner() {
  const player = playedCards.value.player
  const bot = playedCards.value.bot
  if (!player || !bot) return

  let winner = null;
  const trumpSuit = trumpCard.value.suit;

  if (player.suit === trumpSuit && bot.suit !== trumpSuit) { winner = 'p'; }
  else if (bot.suit === trumpSuit && player.suit !== trumpSuit) { winner = 'b'; }
  else if (bot.suit === player.suit) {
      winner = isBigger(player.value, bot.value) ? 'p' : 'b';
  }
  else { winner = iniciateTrick.value === 'p' ? 'p' : 'b'; }

  const points = getCardPoints(player) + getCardPoints(bot);

  if (winner === 'p') {
    playerPoints.value += points;
    iniciateTrick.value = 'p';
  } else {
    botPoints.value += points;
    iniciateTrick.value = 'b';
  }
}

async function clearTable() {
  setTimeout(async () => {
    if (playedCards.value.player) {
        playedCardsHistory.value.push(playedCards.value.player);
        updateTracker(playedCards.value.player);
    }
    if (playedCards.value.bot) {
        playedCardsHistory.value.push(playedCards.value.bot);
        updateTracker(playedCards.value.bot);
    }

    const gameContinues = botCards.value.length > 0;

    if (canDraw.value) {
      if (deck.value.length > 0) {
        if (iniciateTrick.value === 'p') {
          playerCards.value.push(deck.value.pop())
          if (deck.value.length > 0) botCards.value.push(deck.value.pop())
          else {
            botCards.value.push(trumpCard.value)
            canDraw.value = false
          }
        } else {
          botCards.value.push(deck.value.pop())
          if (deck.value.length > 0) playerCards.value.push(deck.value.pop())
          else {
            playerCards.value.push(trumpCard.value)
            canDraw.value = false
          }
        }
      } else {
        if (canDraw.value) {
          if (iniciateTrick.value === 'p') botCards.value.push(trumpCard.value)
          else playerCards.value.push(trumpCard.value)
          canDraw.value = false
        }
      }
      sortPlayerHand()
    }

    playedCards.value = { player: null, bot: null }

    if (!gameContinues) {

      /*try {
        const response = await axios.put(`/api/games/${gameId}/finishGame`, {
            player1_points: playerPoints.value
        })

      } catch (error) {
        console.error('Erro ao finalizar jogo:', error)
      }*/

      isGameOver.value = true;
        const classify = (points) => {
            if (points >= 120) return 'Bandeira';
            if (points >= 91) return 'Capote';
            if (points >= 61) return 'Risca';
            return 'Derrota';
        };
        if (playerPoints.value > botPoints.value) {
            matchWinner.value = `JOGADOR VENCEU (${classify(playerPoints.value)})`;
        } else if (botPoints.value > playerPoints.value) {
            matchWinner.value = `BOT VENCEU (${classify(botPoints.value)})`;
        } else {
            matchWinner.value = 'EMPATE';
        }
    }

    if (iniciateTrick.value === 'b' && gameContinues) {
      setTimeout(() => playBotCard(), 600)
    }
  }, 1000)
}

function restartGame() {
  startGame()
}

function quitGame() {
  router.push('/home')
}

startGame()
</script>

<style scoped>
.card-play-enter-active,
.card-play-leave-active {
  transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.card-play-enter-from,
.card-play-leave-to {
  opacity: 0;
  transform: scale(0.5) translateY(50px);
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}

.game-board {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
}

.player-hand {
  display: flex;
  justify-content: center;
}

.bot-hand {
  display: flex;
  justify-content: center;
  height: 130px;
  gap: 8px;
  align-items: flex-end;
}

.table-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 650px;
  margin: 1rem 0;
}

.deck-area {
  display: flex;
  align-items: center;
}

.deck-and-trump {
  display: flex;
  align-items: flex-end;
  gap: 1.5rem;
}

.deck-stack, .trump-field {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.deck-label {
  margin-top: 0.4rem;
  font-size: 0.85rem;
  color: #fff;
}

.played-area {
  display: flex;
  gap: 2rem;
  justify-content: center;
  min-width: 220px;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  background: #1976d2;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  width: 100%;
  margin-top: 0;
}
.btn:hover {
  background: #145ca1;
}
</style>
