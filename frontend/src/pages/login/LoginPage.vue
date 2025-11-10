<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <Card class="w-full max-w-md">
      <CardHeader class="text-center">
        <CardTitle class="text-3xl font-bold text-slate-900">
          Bisca TAES
        </CardTitle>
        <CardDescription class="text-slate-600">
          Inicia sessão ou entra como anónimo.
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-4">
        <form @submit.prevent="handleLogin" class="space-y-4">

          <div>
            <label for="loginId" class="block text-sm font-medium text-slate-700 mb-2">
              Email ou Nickname
            </label>
            <Input
              id="loginId"
              v-model="loginIdentifier"
              type="text"
              placeholder="o.teu@email.com ou o_teu_nickname"
              class="w-full"
            />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-2">
              Password
            </label>
            <Input
              id="password"
              v-model="password"
              type="password"
              placeholder="A tua password"
              class="w-full"
            />
          </div>

          <Button type="submit" class="w-full">
            Entrar
          </Button>

        </form>
      </CardContent>

      <CardFooter class="flex flex-col">
        <Button variant="secondary" @click="skipLogin" class="w-full">
          Saltar Login (Modo Anónimo)
        </Button>
      </CardFooter>
    </Card>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle
} from '@/components/ui/card'
const router = useRouter()

const loginIdentifier = ref('')
const password = ref('')

const handleLogin = () => {
  console.log('Tentativa de Login com:', loginIdentifier.value)
  router.push('/home') // <-- NAVEGA PARA A HOMEPAGE
}

const skipLogin = () => {
  console.log('A "saltar" login. Entrar em Modo Anónimo.')

  router.push('/home') // <-- NAVEGA PARA A HOMEPAGE
}
</script>
