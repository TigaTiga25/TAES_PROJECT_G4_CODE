<template>
  <div class="game-board">
    <h1>Bisca</h1>

    <!-- Linha 1: mão do bot -->
    <div class="bot-hand">
      <BotHand :cards="botCards" />
    </div>

    <!-- Linha 2: mesa -->
    <div class="table-row">
      <div class="deck-area">
        <div class="deck-and-trump">
          <!-- Baralho -->
          <div class="deck-stack">
            <Card :hidden="true" />
            <span class="deck-label">{{ deck.length }} cards left</span>
          </div>

          <!-- Trunfo -->
          <div class="trump-field">
            <Card :card="trumpCard" />
            <span class="deck-label">Trump</span>
          </div>
        </div>
      </div>

      <!-- Área de jogo -->
      <div class="played-area">
        <div v-if="playedCards.bot" class="played bot">
          <Card :card="playedCards.bot" />
        </div>
        <div v-if="playedCards.player" class="played player">
          <Card :card="playedCards.player" />
        </div>
      </div>
    </div>

    <!-- Linha 3: mão do jogador -->
    <div class="player-hand">
      <PlayerHand :cards="playerCards" @play-card="handlePlayerPlay" />
    </div>

    <div>
        <button @click="restartGame" class="btn">Restart game</button>
        <button @click="quitGame" class="btn">Quit game</button>
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


const fullDeck = generateDeck()

const deck = ref([])
const playerCards = ref([])
const botCards = ref([])
const trumpCard = ref(null)
const playedCards = ref({ player: null, bot: null })
const playerPoints = ref(0)
const botPoints = ref(0)
const iniciateTrick = ref('p') // p = player, b = bot
const canDraw = ref(true)

function startGame() {
  const shuffled = fullDeck.sort(() => Math.random() - 0.5)
  playerCards.value = shuffled.slice(0, 9)
  botCards.value = shuffled.slice(9, 18)
  trumpCard.value = shuffled[39]
  deck.value = shuffled.slice(18, 39)
  playedCards.value = { player: null, bot: null }
  iniciateTrick.value = 'p'
  canDraw.value = true 
}


function handlePlayerPlay(card) {
  //Obrigar a assistir
  if (iniciateTrick.value === 'b' && playedCards.value.bot) {
    const botSuit = playedCards.value.bot.suit
    const hasSuit = playerCards.value.some(c => c.suit === botSuit)
    if (hasSuit && card.suit !== botSuit) {
      toast.error('Tens de assistir ao naipe jogado!')
      
      return
    }
  }

  //verificar se já jogou
  if (
    (playedCards.value.player && iniciateTrick.value === 'p') ||
    (!playedCards.value.bot && iniciateTrick.value === 'b' && playedCards.value.player)
  ) {
    return
  }

  // Jogar carta
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


function playBotCard() {
  if (botCards.value.length === 0) return

  let card = null

  if (iniciateTrick.value === 'b') {
    // Bot começa => joga aleatória
    card = botCards.value[Math.floor(Math.random() * botCards.value.length)]
  } else {
    // Jogador começa => tenta assistir
    const sameSuit = botCards.value.find(c => c.suit === playedCards.value.player.suit)
    if (sameSuit) {
      card = sameSuit
    } else {
      const trump = botCards.value.find(c => c.suit === trumpCard.value.suit)
      card = trump || botCards.value[Math.floor(Math.random() * botCards.value.length)]
    }
  }

  playedCards.value.bot = card
  botCards.value = botCards.value.filter(c => c !== card)

  if (iniciateTrick.value === 'p') {
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

  if (player.suit === trumpCard.value.suit && bot.suit !== trumpCard.value.suit) {
    //ganha o jogador porque jogou trunfo
    iniciateTrick.value = 'p'
  } else if (bot.suit === trumpCard.value.suit && player.suit !== trumpCard.value.suit) {
    //ganha o bot porque jogou trunfo
    iniciateTrick.value = 'b'
  } else if (bot.suit === player.suit) {
    //ganha quem tiver jogado a carta mais alta pois os naipes são os mesmos
    iniciateTrick.value = isBigger(player.value, bot.value) ? 'p' : 'b'
  } else {
    // se jogaram naipes diferentes e sem trunfo, ganha quem começou
    iniciateTrick.value = iniciateTrick.value === 'p' ? 'p' : 'b'
  }
}



function clearTable() {
  setTimeout(() => {
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
    }

    // Limpar mesa
    playedCards.value = { player: null, bot: null }

    // Bot começa se ganhou
    if (iniciateTrick.value === 'b') {
      setTimeout(() => playBotCard(), 600)
    }
  }, 1000)
}


//Retorna true se a carta 1 for maior que a carta 2
function isBigger(card1, card2) {
  const order = ['2','3','4','5','6','12','11','13','7','1']
  return order.indexOf(card1) > order.indexOf(card2)
}

function restartGame() {
  startGame()
}

function quitGame() {
  //TODO
}

startGame()
</script>



<style scoped>
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
  gap: 1rem;
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
  color: #555;
}

.played-area {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  min-width: 180px;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  background: #1976d2;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  width: 300px;
  margin-top: 100px;
  margin-right: 20px;
  margin-left: 20px;
}
.btn:hover {
  background: #145ca1;
}
</style>
