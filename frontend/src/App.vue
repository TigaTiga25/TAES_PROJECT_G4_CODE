<template>
  <Toaster />

  <!-- NAVBAR -->
  <Navbar />

  <!-- Onde as páginas vão aparecer -->
  <router-view />
</template>

<script setup>
import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css'

import Navbar from '@/components/navbar.vue'

</script>
