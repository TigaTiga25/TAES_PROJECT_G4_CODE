<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <Card class="w-full max-w-md">
      <CardHeader class="text-center">
        <CardTitle class="text-3xl font-bold text-slate-900">
          Bisca TAES
        </CardTitle>
        <CardDescription class="text-slate-600">
          Log in or enter as a guest.
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-4">
        <form @submit.prevent="handleLogin" class="space-y-4">

          <div>
            <label for="loginId" class="block text-sm font-medium text-slate-700 mb-2">
              Email
              </label>
            <Input
              id="loginId"
              v-model="loginIdentifier"
              type="text"
              placeholder="o.teu@email.com"
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

          <p v-if="errorMessage" class="text-red-600 text-sm text-center">
            {{ errorMessage }}
          </p>

          <Button type="submit" class="w-full">
            Log In
          </Button>

          <Button type="button" class="w-full" @click="goToRegister">
            Sign Up
          </Button>

        </form>
      </CardContent>

      <CardFooter class="flex flex-col">
        <Button variant="secondary" @click="skipLogin" class="w-full">
          Continue as Guest
        </Button>
      </CardFooter>
    </Card>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { userStore } from '@/stores/userStore.js'
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

const loginIdentifier = ref('')
const password = ref('')
const errorMessage = ref(null) // Para guardar mensagens de erro

// --- LOGIN NORMAL (CORRIGIDO) ---
const handleLogin = async () => {
  errorMessage.value = null // Limpa erros antigos

  // 1. Validar se os campos estão preenchidos
  if (!loginIdentifier.value || !password.value) {
    errorMessage.value = "Por favor, preencha o email e a password."
    return
  }

  try {
    // 2. Tentar fazer o pedido ao backend
    // O URL '/api/login' é relativo (ex: http://localhost:8000/api/login)
    const response = await axios.post('/api/login', {
      email: loginIdentifier.value, // O backend espera um campo 'email'
      password: password.value
    });

    // 3. SUCESSO! O backend aceitou o login e enviou um token
    const token = response.data.token;

    // 4. Chamar a store
   userStore.login(response.data.token, response.data.user)

    // Isto garante que o jogo sabe qual o baralho logo ao entrar
    const myDeck = response.data.user.current_deck || 'default';
    localStorage.setItem('userDeck', myDeck);


    // 5. Redirecionar para a página principal
    router.push('/home');

  } catch (error) {
    // 6. FALHA! O backend enviou um erro (ex: 401)

    // A userStore.login() NUNCA é chamada

    if (error.response && error.response.data && error.response.data.message) {
      // Mostra a mensagem de erro vinda do Laravel (ex: "Email ou password inválida.")
      errorMessage.value = error.response.data.message;
    } else {
      // Erro genérico (ex: rede, servidor desligado)
      errorMessage.value = "Não foi possível ligar ao servidor. Tente mais tarde."
    }
    console.error('Falha no login:', error);
  }
}

// --- LOGIN ANÓNIMO ---
const skipLogin = () => {
  userStore.loginAsGuest()
  router.push('/home')
}

// --- IR PARA PÁGINA DE REGISTO ---
const goToRegister = () => {
  router.push('/register')
}
</script>
