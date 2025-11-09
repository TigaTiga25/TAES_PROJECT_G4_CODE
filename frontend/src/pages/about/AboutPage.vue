<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">

      <div class="text-center space-y-2">
        <h1 class="text-4xl font-bold text-slate-900 tracking-tight">
          Projeto de Bisca (TAES)
        </h1>
        <p class="text-lg text-slate-600">
          Página "Sobre" e Teste de Ligação ao CSS
        </p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <p class="text-slate-700 leading-relaxed">
          Esta página serve como a "Página Sobre" do projeto de Bisca single-player
          e também como prova de que o
          <span class="font-semibold text-emerald-600">Frontend (VueJS)</span>
          está a comunicar com o
          <span class="font-semibold text-red-600">Backend (o nosso "CSS" em Laravel)</span>.
        </p>
      </div>

      <section class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-4">
          <h3 class="text-xl font-semibold text-white flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            REST API (O nosso "CSS")
          </h3>
        </div>
        <div class="p-6 space-y-4">
          <div class="flex items-center justify-between py-3 border-b border-slate-100">
            <span class="text-sm font-medium text-slate-600">API Name</span>
            <span class="text-base font-semibold text-slate-900 bg-slate-100 px-3 py-1 rounded-md">
              {{ api.name }} </span>
          </div>
          <div class="flex items-center justify-between py-3">
            <span class="text-sm font-medium text-slate-600">API Version</span>
            <span
              class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800">
              v{{ api.version }}
            </span>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>

import { ref, onMounted } from 'vue'

const api = ref({})

onMounted(async () => {
  const response = await fetch('http://localhost:8000/api/metadata', {
    method: 'GET',
    headers: {
      'Accept': 'application/json'
    }
  })
  api.value = await response.json()
})
</script>
