<template>
  <div class="card">
    <img :src="imageSrc" alt="carta" class="card-img" draggable="false" />
  </div>
</template>

<script setup>
import { computed } from 'vue'

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

// --- AQUI ESTÁ A MUDANÇA ---
// Não importamos nada de stores. Usamos lógica simples.

const imageSrc = computed(() => {
  // 1. Tenta ler da memória do browser. Se não tiver nada, usa 'default'.
  // Isto evita teres de criar a "Store" complexa agora.
  const currentDeck = localStorage.getItem('userDeck') || 'default'

  // 2. Se a carta estiver escondida (ex: baralho na mesa)
  if (props.hidden) {
    return `/decks/${currentDeck}/verso.png`
  }

  // 3. Se não houver carta definida
  if (!props.card) return ''

  // 4. Constrói o caminho da imagem
  return `/decks/${currentDeck}/${props.card.suit}${props.card.value}.png`
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
  object-fit: cover;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}
</style>