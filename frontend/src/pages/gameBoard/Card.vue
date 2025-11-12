<template>
  <div class="card">
    <img :src="imageSrc" alt="carta" class="card-img" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import images from '@/lib/cardImage.js'

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

const imageSrc = computed(() => {
  if (props.hidden) return images['semFace']

  const key = `${props.card.suit}${props.card.value}`
  return images[key] || images['semFace']
})
</script>

<style scoped>
.card {
  width: 80px;
  height: 120px;
}
.card-img {
  width: 100%;
  height: 100%;
  border-radius: 6px;
  object-fit: cover;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
</style>
