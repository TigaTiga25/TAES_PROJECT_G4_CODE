<script setup>
import { ref, computed, onMounted } from "vue"
import { useRouter } from "vue-router"
import { userStore } from '@/stores/userStore.js' 
import axios from 'axios'

// UI Components
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Button } from "@/components/ui/button"

const router = useRouter()
const fileInputRef = ref(null) // Referência para o input invisível

// Dados do formulário
const form = ref({
  name: userStore.user?.name || '',
  password: ""
})

// Variáveis para o upload
const selectedFile = ref(null)
const previewImage = ref(null)


const API_URL = 'http://localhost:8000'



const displayImage = computed(() => {
   
    if (previewImage.value) {
        return previewImage.value
    }

    
    if (userStore.user?.photo_avatar_filename) {
        return `${API_URL}/storage/photos_avatars/${userStore.user.photo_avatar_filename}`
    }

    // 3. Fallback: Retorna null para ativar o v-else das iniciais
    return null
})

// Quando o utilizador seleciona um ficheiro
function onFileChange(event) {
    const file = event.target.files[0]
    if (!file) return

    selectedFile.value = file
    previewImage.value = URL.createObjectURL(file)
}


function triggerFileInput() {
    fileInputRef.value.click()
}

async function saveProfile() {
    
    if (!userStore.user || !userStore.user.id) {
        alert("Erro: Utilizador não identificado. Faz login novamente.")
        return
    }

    try {
        const formData = new FormData()

     
        formData.append('_method', 'PUT')

        // Dados normais
        formData.append('name', form.value.name)

        if (form.value.password) {
            formData.append('password', form.value.password)
        }

       
        if (selectedFile.value) {
            formData.append('file', selectedFile.value) 
        }

        // Enviar pedido
       
        const response = await axios.post(
            `${API_URL}/api/users/${userStore.user.id}`, 
            formData,
            {
                headers: { 'Content-Type': 'multipart/form-data' }
            }
        )

       
        const updatedUser = response.data.data 
        userStore.login(userStore.token, updatedUser)

        alert("Perfil atualizado com sucesso!")

        // Limpezas
        selectedFile.value = null
        previewImage.value = null
        form.value.password = ""

    } catch (error) {
        console.error("Erro no update:", error)
        if (error.response?.status === 422) {
            // Erro de validação do Laravel (ex: imagem muito grande)
            console.log("Erros de validação:", error.response.data.errors)
            alert("Erro nos dados: " + JSON.stringify(error.response.data.errors))
        } else {
            alert("Erro ao guardar. Verifica a consola.")
        }
    }
}

onMounted(() => {
    if (!userStore.isLoggedIn) router.push('/')
})
</script>

<template>
  <div class="max-w-xl mx-auto p-6 space-y-8">
    <h1 class="text-3xl font-bold">Perfil</h1>

    <Card>
      <CardHeader>
        <CardTitle>Editar Perfil</CardTitle>
        <CardDescription>Muda a tua foto e nome.</CardDescription>
      </CardHeader>

      <CardContent class="space-y-6">
        
        <div class="flex items-center gap-6">
            
            <div 
                class="w-24 h-24 rounded-2xl overflow-hidden bg-emerald-600 text-white flex items-center justify-center shadow-md font-bold border-2 border-emerald-100 shrink-0 relative group"
            >
                <img
                  v-if="displayImage"
                  :src="displayImage"
                  alt="Avatar"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-3xl">
                    {{ form.name?.[0]?.toUpperCase() || '?' }}
                </span>

                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition flex items-center justify-center pointer-events-none">
                    📷
                </div>
            </div>

            <div class="space-y-2">
                <Label>Foto de Perfil</Label>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="triggerFileInput">
                        Escolher Ficheiro
                    </Button>
                    <input 
                        ref="fileInputRef"
                        type="file" 
                        class="hidden" 
                        accept="image/jpeg,image/png,image/jpg"
                        @change="onFileChange"
                    />
                </div>
                <p class="text-xs text-slate-500">
                    Aceita JPG ou PNG. Máx 2MB.
                </p>
            </div>
        </div>

        <div class="space-y-2">
          <Label>Nome de Jogador</Label>
          <Input v-model="form.name" placeholder="O teu nome" />
        </div>

        <div class="space-y-2 pt-4 border-t">
          <Label>Nova Password (Opcional)</Label>
          <Input type="password" v-model="form.password" />
        </div>

      </CardContent>

      <CardFooter class="flex justify-between">
        <Button variant="ghost" @click="router.push('/home')">Cancelar</Button>
        <Button @click="saveProfile">Guardar Alterações</Button>
      </CardFooter>
    </Card>
  </div>
</template>