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

          <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
              Email
            </label>
            <Input
              v-model="email"
              type="email"
              placeholder="your@email.com"
              class="w-full"
            />
            <p v-if="validationErrors?.email" class="text-sm text-red-600 mt-1">
              {{ validationErrors.email[0] }}
            </p>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
              Name
            </label>
            <Input
              v-model="nickname"
              type="text"
              placeholder="Your name"
              class="w-full"
            />
            <p v-if="validationErrors?.name" class="text-sm text-red-600 mt-1">
              {{ validationErrors.name[0] }}
            </p>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
              Password
            </label>
            <Input
              v-model="password"
              type="password"
              placeholder="Your password"
              class="w-full"
            />
            <p v-if="validationErrors?.password" class="text-sm text-red-600 mt-1">
              {{ validationErrors.password[0] }}
            </p>
          </div>

          <div v-if="errorMessage" class="p-3 bg-red-100 text-red-700 rounded text-sm">
            {{ errorMessage }}
          </div>

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
import axios from 'axios' 

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

// Variáveis do formulário
const email = ref('')
const nickname = ref('')
const password = ref('')

// Estado de erros
const errorMessage = ref(null)
const validationErrors = ref(null)

const handleRegister = async () => {
  errorMessage.value = null
  validationErrors.value = null

  try {
    const response = await axios.post('/api/register', {
      name: nickname.value, 
      email: email.value,
      password: password.value
    });

    alert(response.data.message);
    router.push('/'); // Redireciona para o login

  } catch (error) {
    if (error.response) {
      if (error.response.status === 422) {
        // Erros de validação (ex: email já existe)
        validationErrors.value = error.response.data.errors;
      } else {
        // Erros de servidor (500, etc)
        errorMessage.value = "Ocorreu um erro no servidor. Tente novamente mais tarde.";
        console.error("Erro do servidor:", error.response.data);
      }
    } else {
      errorMessage.value = "Não foi possível ligar ao servidor.";
    }
  }
}

const goBack = () => {
  router.push('/')
}
</script>