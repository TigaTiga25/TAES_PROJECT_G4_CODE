<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <Card class="w-full max-w-md shadow-lg border border-slate-200">
      <CardHeader class="text-center space-y-2">
        <CardTitle class="text-3xl font-bold text-slate-900">
          Create a new account
        </CardTitle>
        <CardDescription class="text-slate-600">
          Join Bisca TAES and unlock all features!
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-5">
        <form @submit.prevent="handleRegister" class="space-y-5">

          <!-- Email -->
          <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
              Email
            </label>
            <Input
              v-model="email"
              type="email"
              placeholder="o.teu@email.com"
              class="w-full"
            />
            <p v-if="validationErrors" class="text-sm text-red-600 mt-1" >{{ validationErrors.email?validationErrors.email[0]: '' }}</p>
          </div>

          <!-- Nickname -->
          <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
              Nickname
            </label>
            <Input
              v-model="nickname"
              type="text"
              placeholder="O teu nickname"
              class="w-full"
            />
            <p v-if="validationErrors" class="text-sm text-red-600 mt-1">{{ validationErrors.name?validationErrors.name[0]: '' }}</p>
          </div>

          <!-- Password -->
          <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
              Password
            </label>
            <Input
              v-model="password"
              type="password"
              placeholder="A tua password"
              class="w-full"
            />
            <p v-if="validationErrors"class="text-sm text-red-600 mt-1">{{ validationErrors.password?validationErrors.password[0]: '' }}</p>
          </div>

          <!-- Submit -->
          <Button type="submit" class="w-full py-3 text-base">
            Sign Up
          </Button>

        </form>
      </CardContent>

      <CardFooter class="flex flex-col gap-3 mt-2">
        <Button variant="secondary" @click="goBack" class="w-full">
          Return to Login
        </Button>
      </CardFooter>
    </Card>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios' // 1. IMPORTAR O AXIOS

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

const email = ref('')
const nickname = ref('')
const password = ref('')

// 2. ADICIONAR ESTADO PARA ERROS
const errorMessage = ref(null)
const validationErrors = ref(null)

// 3. SUBSTITUIR A FUNÇÃO DE REGISTO
const handleRegister = async () => {
  errorMessage.value = null
  validationErrors.value = null

  try {
    // 4. Enviar os dados para a API (Laravel)
    // O backend espera 'name', por isso enviamos o 'nickname' como 'name'
    const response = await axios.post('/api/register', {
      name: nickname.value,
      email: email.value,
      password: password.value
    });

     // "Registo efetuado... verifique o email"
    alert(response.data.message);

    // 7. Redirecionar
    router.push('/');

  } catch (error) {
    // 8. FALHA! (Ex: Email já existe, password curta, etc.)
    if (error.response) {
      if (error.response.status === 422) {
        // 422 = Erro de Validação (ex: 'email já existe' ou 'password curta')
        validationErrors.value = error.response.data.errors;
      } else {
        // Outro erro (ex: 500)
        errorMessage.value = "Ocorreu um erro inesperado. Tente mais tarde."
      }
    } else {
      // Erro de rede (servidor desligado?)
      errorMessage.value = "Não foi possível ligar ao servidor."
    }
    console.error('Falha no registo:', error);
  }
}

const goBack = () => {
  router.push('/')
}
</script>
