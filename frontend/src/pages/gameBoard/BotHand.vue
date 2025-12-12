<template>
  <div class="bot-hand">
    <Card
      v-for="(card, i) in cards"
      :key="i"
      :card="card"
      hidden
    />
  </div>
</template>

<script setup>
import Card from './PlayingCard.vue'

defineProps({
  cards: {
    type: Array,
    required: true
  }
})
</script>

<style scoped>
.bot-hand {
  display: flex;
  gap: 10px;
  justify-content: center;
}
</style>
