<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <Card class="w-full max-w-md shadow-lg border border-slate-200">
      <CardHeader class="text-center space-y-2">
        <CardTitle class="text-3xl font-bold text-slate-900">
          Criar Conta
        </CardTitle>
        <CardDescription class="text-slate-600">
          Junta-te à Bisca TAES e desbloqueia todas as funcionalidades!
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
          </div>

          <!-- Submit -->
          <Button type="submit" class="w-full py-3 text-base">
            Criar Conta
          </Button>

        </form>
      </CardContent>

      <CardFooter class="flex flex-col gap-3 mt-2">
        <Button variant="secondary" @click="goBack" class="w-full">
          Voltar ao Login
        </Button>
      </CardFooter>
    </Card>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { userStore } from '@/stores/userStore.js'
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

    // 5. SUCESSO! Utilizador guardado na base de dados!
    // O backend envia um token, tal como no login
    const token = response.data.token;

    // 6. Fazer login automático com o token
    userStore.login(token);

    // 7. Redirecionar
    router.push('/home');

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
