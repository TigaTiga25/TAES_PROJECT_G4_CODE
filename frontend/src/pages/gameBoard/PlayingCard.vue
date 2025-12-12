<template>
  <div class="card">
    <img
      :src="imageSrc"
      alt="carta"
      class="card-img"
      draggable="false"
      @error="$event.target.src = `/decks/default/${cardIdentifier}.png`"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { userStore } from '@/stores/userStore.js' 

const props = defineProps({
  card: {
    type: Object,
    required: false
  },
  hidden: {
    type: Boolean,
    default: false
  }
})

// Helper para saber o nome do ficheiro (ex: c1, p12)
const cardIdentifier = computed(() => {
    if (!props.card) return 'verso'
    return `${props.card.suit}${props.card.value}`
})

const imageSrc = computed(() => {
  const deckFolder = userStore.user?.current_deck || 'default'

  // 2. Se a carta estiver escondida (verso)
  if (props.hidden) {
    return `/decks/${deckFolder}/verso.png`
  }

  // 3. Carta normal (ex: /decks/pixel/c1.png)
  if (props.card) {
    return `/decks/${deckFolder}/${cardIdentifier.value}.png`
  }

  return ''
})
</script>

<style scoped>
.card {
  width: 110px;
  height: 165px;
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-5px);
}

.card-img {
  width: 100%;
  height: 100%;
  border-radius: 6px;
  object-fit: cover; /* Garante que a imagem preenche o card sem deformar */
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}
</style>
