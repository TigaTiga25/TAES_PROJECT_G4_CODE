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

// Avatar Components
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"

const router = useRouter()
const fileInputRef = ref(null) 

// Form data
const form = ref({
  name: userStore.user?.name || '',
  password: ""
})

// Upload variables
const selectedFile = ref(null)
const previewImage = ref(null)

// Define a tua API URL
const API_URL = 'http://localhost:8000'

// Lógica de visualização da imagem
const displayImage = computed(() => {
    // 1. Prioridade: Preview local (se o user acabou de escolher um ficheiro)
    if (previewImage.value) return previewImage.value
    
    // 2. Prioridade: Foto da base de dados
    if (userStore.user?.photo_avatar_filename) {
        return `${API_URL}/storage/photos_avatars/${userStore.user.photo_avatar_filename}`
    }

    // 3. Fallback: Null (o componente Avatar trata disto sozinho e mostra as iniciais)
    return null
})

// Iniciais do nome para o Fallback (ex: "João Silva" -> "J")
const userInitials = computed(() => {
    const name = form.value.name || userStore.user?.name || '?'
    return name.charAt(0).toUpperCase()
})

// Quando o user escolhe um ficheiro
function onFileChange(event) {
    const file = event.target.files[0]
    if (!file) return
    selectedFile.value = file
    previewImage.value = URL.createObjectURL(file)
}

// Clicar no botão aciona o input escondido
function triggerFileInput() {
    fileInputRef.value.click()
}

// Guardar perfil e enviar ficheiro
async function saveProfile() {
    if (!userStore.user?.id) return

    try {
        const formData = new FormData()
        
        // Método spoofing para Laravel (PUT via POST) para aceitar ficheiros
        formData.append('_method', 'PUT')
        
        formData.append('name', form.value.name)
        
        if (form.value.password) {
            formData.append('password', form.value.password)
        }
        
        // IMPORTANTE: O nome 'file' tem de bater certo com o Controller do Laravel
        if (selectedFile.value) {
            formData.append('file', selectedFile.value) 
        }

        const response = await axios.post(
            `${API_URL}/api/users/${userStore.user.id}`, 
            formData, 
            {
                headers: { 'Content-Type': 'multipart/form-data' }
            }
        )

        // Atualizar a Store com os dados novos 
        userStore.login(userStore.token, response.data.data)
        
        alert("Profile updated successfully!")
        
        // Limpezas
        selectedFile.value = null
        previewImage.value = null // Limpa o preview para assumir a nova foto da BD
        form.value.password = ""

    } catch (error) {
        console.error(error)
        if (error.response?.status === 422) {
             alert("Validation Error: " + JSON.stringify(error.response.data.errors))
        } else {
             alert("Error updating profile.")
        }
    }
}

onMounted(() => {
    if (!userStore.isLoggedIn) router.push('/')
})
</script>

<template>
  <div class="max-w-xl mx-auto p-6 space-y-8">
    <h1 class="text-3xl font-bold">Profile</h1>

    <Card>
      <CardHeader>
        <CardTitle>Edit Profile</CardTitle>
        <CardDescription>Change your photo and name.</CardDescription>
      </CardHeader>

      <CardContent class="space-y-6">
        
        <div class="flex items-center gap-6">
            
            <!-- Componente Avatar -->
            <Avatar class="w-24 h-24 border-2 border-emerald-100 shadow-md">
                <!-- Imagem (só aparece se displayImage for válido) -->
                <AvatarImage 
                    
                    :src="displayImage" 
                    class="object-cover" 
                    alt="User Photo" 
                />
                <!-- Fallback (aparece se não houver imagem) -->
                <AvatarFallback class="bg-emerald-600 text-white text-3xl font-bold">
                    {{ userInitials }}
                </AvatarFallback>
            </Avatar>

            <div class="space-y-2">
                <Label>Profile Photo</Label>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="triggerFileInput">
                        Choose File
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
                    Max 4MB (JPG/PNG).
                </p>
            </div>
        </div>

        <div class="space-y-2">
          <Label>Player Name</Label>
          <Input v-model="form.name" placeholder="Your name" />
        </div>

        <div class="space-y-2 pt-4 border-t">
          <Label>New Password (Optional)</Label>
          <Input type="password" v-model="form.password" />
        </div>

      </CardContent>

      <CardFooter class="flex justify-between">
        <Button variant="ghost" @click="router.push('/home')">Cancel</Button>
        <Button @click="saveProfile">Save Changes</Button>
      </CardFooter>
    </Card>
  </div>
</template>