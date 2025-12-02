<script setup>
import { computed, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { userStore } from '@/stores/userStore.js'

// --- UI Components ---
import {
  NavigationMenu,
  NavigationMenuList,
  NavigationMenuItem
} from '@/components/ui/navigation-menu'

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'

// --- Logic ---
const router = useRouter()
const route = useRoute()
// O API_URL já não é estritamente necessário aqui se não carregarmos fotos reais, 
// mas podes deixar ficar.
const API_URL = 'http://localhost:8000' 

const showAvatarModal = ref(false)

const showNavbar = computed(() => {
  const hiddenRoutes = ['/', '/login', '/register', '/gameBoard/'];
  return !hiddenRoutes.some(r => route.path === r || route.path.startsWith('/gameBoard/'));
});

// --- MUDANÇA AQUI: Lógica simplificada ---
const avatarUrl = computed(() => {
  const u = userStore.user
  
  // ANTES: Verificava se tinha foto real...
  // AGORA: Ignoramos a foto real na navbar. Queremos sempre o boneco.

  // 1. Tenta usar a seed personalizada (do passo anterior)
  // 2. Se não tiver, usa o nome do utilizador
  // 3. Fallback para 'Player'
  const seed = u?.custom_avatar_seed || u?.name || 'Player'
  
  return `https://api.dicebear.com/7.x/avataaars/svg?seed=${seed}`
})

const initials = computed(() => {
  const u = userStore.user
  return u?.name ? u.name[0].toUpperCase() : '?'
})

// Funções de Navegação
function goHome() { router.push('/home') }
function goTo(path) { router.push(path) }

function handleLogout() {
  userStore.logout()
  router.push('/')
}
</script>

<template>
  <nav v-if="showNavbar" class="border-b bg-white/90 backdrop-blur-sm sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">

      <div class="flex items-center gap-3 cursor-pointer" @click="goHome">
        <span class="text-xl font-bold">BISCA</span>
        <span class="text-sm text-slate-600">Single Player</span>
      </div>

      <NavigationMenu class="hidden md:flex">
        <NavigationMenuList>
          
          <NavigationMenuItem v-if="userStore.isLoggedIn">
            <RouterLink to="/customizations" class="nav-link">Customizations</RouterLink>
          </NavigationMenuItem>

          <NavigationMenuItem v-if="userStore.isLoggedIn">
            <RouterLink to="/history" class="nav-link">Game History</RouterLink>
          </NavigationMenuItem>
          
          <NavigationMenuItem v-if="userStore.isLoggedIn">
            <RouterLink to="/scoreboards" class="nav-link">Scoreboards</RouterLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <RouterLink to="/about" class="nav-link">About</RouterLink>
          </NavigationMenuItem>

        </NavigationMenuList>
       </NavigationMenu>

      <div class="flex items-center gap-4">
        
        <NavigationMenu v-if="!userStore.isLoggedIn">
          <NavigationMenuList>
            <NavigationMenuItem>
              <RouterLink to="/" class="nav-link">Login</RouterLink>
            </NavigationMenuItem>
          </NavigationMenuList>
        </NavigationMenu>
        
        <template v-else>
            <Badge 
              variant="secondary" 
              class="px-3 py-1 text-sm cursor-pointer hover:bg-slate-200 transition select-none"
              @click="goTo('/transactions')" 
            >
              🪙 {{ userStore.user?.coins_balance || 0 }}
            </Badge>

            <DropdownMenu>
              <DropdownMenuTrigger class="focus:outline-none flex items-center gap-3 group">
                
                <span class="font-medium text-sm text-slate-700 group-hover:text-black transition hidden sm:block">
                  {{ userStore.user?.name || 'Player' }}
                </span>
                
                <Avatar 
                    class="cursor-pointer bg-slate-100 group-hover:ring-2 group-hover:ring-slate-200 transition"
                >
                  <AvatarImage :src="avatarUrl" class="object-cover" />
                  <AvatarFallback class="bg-slate-200 text-slate-700 font-bold">
                    {{ initials }}
                  </AvatarFallback>
                </Avatar>

              </DropdownMenuTrigger>

              <DropdownMenuContent class="w-48 mr-4">
                <DropdownMenuItem @click="goTo('/profile')" class="cursor-pointer">
                  👤 Profile
                </DropdownMenuItem>

                <DropdownMenuItem @click="showAvatarModal = true" class="cursor-pointer">
                  🖼️ View Avatar
                </DropdownMenuItem>
                
                <DropdownMenuSeparator />
                
                <DropdownMenuItem @click="handleLogout" class="cursor-pointer text-red-600 focus:text-red-600">
                  🚪 Logout
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
        </template>

      </div>
    </div>
  </nav>

  <div v-if="showAvatarModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-200" @click="showAvatarModal = false">
    
    <div class="bg-white p-2 rounded-xl shadow-2xl max-w-sm w-full relative" @click.stop>
      
      <div class="flex justify-between items-center p-2 border-b mb-2">
        <h3 class="font-bold text-slate-700">Your Avatar</h3>
        <button @click="showAvatarModal = false" class="text-slate-400 hover:text-red-500 transition px-2 text-xl font-bold">
          &times;
        </button>
      </div>

      <div class="flex justify-center bg-slate-50 rounded-lg p-6">
        <img 
          :src="avatarUrl" 
          alt="User Avatar" 
          class="w-64 h-64 object-cover rounded-full border-4 border-white shadow-md"
        >
      </div>

      <div class="mt-4 mb-2 text-center">
        <p class="text-lg font-medium text-slate-800">
          {{ userStore.user?.name || 'Player' }}
        </p>
      </div>

    </div>
  </div>
</template>

<style scoped>
.nav-link {
  padding: 8px 14px;
  border-radius: 6px;
  font-weight: 500;
  transition: background .2s;
}
.nav-link:hover {
  background: rgba(0,0,0,0.08);
}

.router-link-active {
  background: rgba(0,0,0,0.05);
  color: #000;
  font-weight: 600;
}
</style>