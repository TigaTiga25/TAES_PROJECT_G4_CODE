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
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"

const router = useRouter()
const fileInputRef = ref(null) 

const API_URL = 'http://localhost:8000'

// ---------------------------------------------------------
// 1. CONFIGURAÇÃO DO FORMULÁRIO
// ---------------------------------------------------------
const form = ref({
  // Campos BLOQUEADOS (Apenas leitura)
  name: userStore.user?.name || '', 
  email: userStore.user?.email || '',
  
  // Campos EDITÁVEIS
  nickname: userStore.user?.nickname || '', 
  password: ""
})


// ---------------------------------------------------------
// 2. LÓGICA DA FOTO (FOTO REAL)
// ---------------------------------------------------------
const selectedFile = ref(null)
const previewImage = ref(null)

const displayProfilePhoto = computed(() => {
    if (previewImage.value) return previewImage.value
    if (userStore.user?.photo_avatar_filename) {
        return `${API_URL}/storage/photos_avatars/${userStore.user.photo_avatar_filename}`
    }
    return null
})

const userInitials = computed(() => {
    const name = form.value.name || '?'
    return name.charAt(0).toUpperCase()
})

function onFileChange(event) {
    const file = event.target.files[0]
    if (!file) return
    selectedFile.value = file
    previewImage.value = URL.createObjectURL(file)
}

function triggerFileInput() {
    fileInputRef.value.click()
}

// ---------------------------------------------------------
// 3. SALVAR (UPDATE)
// ---------------------------------------------------------
async function saveProfile() {
    if (!userStore.user?.id) return

    try {
        const formData = new FormData()
        formData.append('_method', 'PUT')
        
        // ENVIA APENAS O QUE É EDITÁVEL
        
        // 1. Player Name
        formData.append('nickname', form.value.nickname)

        // 2. Senha (se preenchida)
        if (form.value.password) {
            formData.append('password', form.value.password)
        }
        
        // 3. Foto Real (se selecionada)
        if (selectedFile.value) {
            formData.append('file', selectedFile.value) 
        }

        // Nota: NÃO enviamos 'name' nem 'email' pois são bloqueados.

        const response = await axios.post(
            `${API_URL}/api/users/${userStore.user.id}`, 
            formData, 
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )

        userStore.login(userStore.token, response.data.data)
        alert("Profile updated successfully!")
        
        selectedFile.value = null
        form.value.password = ""

    } catch (error) {
        console.error(error)
        alert("Error updating profile: " + (error.response?.data?.message || error.message))
    }
}

onMounted(() => {
    if (!userStore.isLoggedIn) router.push('/')
})
</script>

<template>
  <div class="max-w-2xl mx-auto p-6 space-y-8">
    <h1 class="text-3xl font-bold">Account Settings</h1>

    <Card>
      <CardHeader>
        <CardTitle>Personal Details</CardTitle>
        <CardDescription>Manage your personal information.</CardDescription>
      </CardHeader>

      <CardContent class="space-y-8">
        
        <div class="flex items-center gap-6 pb-6 border-b border-slate-100">
            <Avatar class="w-20 h-20 border-2 border-slate-200">
                <AvatarImage :src="displayProfilePhoto" class="object-cover" />
                <AvatarFallback class="bg-slate-800 text-white text-xl">
                    {{ userInitials }}
                </AvatarFallback>
            </Avatar>

            <div class="space-y-2">
                <Label>Profile Photo</Label>
                <div class="flex gap-2">
                    <Button variant="secondary" size="sm" @click="triggerFileInput">
                        Change Photo
                    </Button>
                    <input ref="fileInputRef" type="file" class="hidden" accept="image/*" @change="onFileChange" />
                </div>
            </div>
        </div>

        <div class="grid gap-5">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label>Name</Label>
                    <Input 
                        v-model="form.name" 
                        disabled 
                        class="bg-slate-50 text-slate-500 cursor-not-allowed" 
                    />
                </div>

                <div class="space-y-2">
                    <Label class="text-indigo-600 font-semibold">Player Name</Label>
                    <Input 
                        v-model="form.nickname" 
                        placeholder="Enter your gamer tag" 
                        class="border-indigo-200 focus-visible:ring-indigo-500"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <Label>Email</Label>
                <Input 
                    v-model="form.email" 
                    disabled 
                    class="bg-slate-50 text-slate-500 cursor-not-allowed" 
                />
            </div>

            <div class="space-y-2 pt-2">
                <Label>New Password (Optional)</Label>
                <Input 
                    type="password" 
                    v-model="form.password" 
                    placeholder="Leave empty to keep current password"
                    autocomplete="new-password" 
                />
            </div>
        </div>

      </CardContent>

      <CardFooter class="bg-slate-50/50 p-6 flex justify-end">
        <Button @click="saveProfile">Save Changes</Button>
      </CardFooter>
    </Card>
  </div>
</template>