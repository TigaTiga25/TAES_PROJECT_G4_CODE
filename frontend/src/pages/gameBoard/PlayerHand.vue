<template>
  <div class="player-hand">
    <div
      v-for="card in cards"
      :key="card.suit + card.value"
      class="card-container"
      @click="play(card)"
    >
      <Card :card="card" />
    </div>
  </div>
</template>

<script setup>
import Card from './Card.vue'

defineProps({
  cards: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['play-card'])

function play(card) {
  emit('play-card', card)
}
</script>

<style scoped>
.player-hand {
  display: flex;
  gap: 10px;
  justify-content: center;
}
.card-container {
  cursor: pointer;
  transition: transform 0.2s;
}
.card-container:hover {
  transform: translateY(-10px);
}
</style>
