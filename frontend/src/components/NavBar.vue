<script setup>
import { computed } from 'vue'
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

// --- Lógica ---
const router = useRouter()
const route = useRoute()

// Define se a barra aparece
const showNavbar = computed(() => {
  const hiddenRoutes = ['/', '/login', '/register', '/gameBoard/'];
  return !hiddenRoutes.some(r => route.path === r || route.path.startsWith('/gameBoard/'));
});

// --- AVATAR ---
const avatarUrl = computed(() => {
  const u = userStore.user
  
  // 1. Imagem real
  if (u && u.avatar) {
    return u.avatar
  }
  // 2. Fallback DiceBear
  const seed = u?.name || 'Player'
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
          <NavigationMenuItem>
            <RouterLink to="/customizations" class="nav-link">Customizations</RouterLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <RouterLink to="/history" class="nav-link">Game History</RouterLink>
          </NavigationMenuItem>
          
          <NavigationMenuItem>
            <RouterLink to="/scoreboards" class="nav-link">Scoreboards</RouterLink>
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
        
       <Badge 
          v-if="userStore.isLoggedIn" 
          variant="secondary" 
          class="px-3 py-1 text-sm cursor-pointer hover:bg-slate-200 transition select-none"
          @click="goTo('/transactions')" 
        >
          🪙 {{ userStore.user?.coins_balance || 0 }}
        </Badge>

        <DropdownMenu v-if="userStore.isLoggedIn">
          <DropdownMenuTrigger class="focus:outline-none flex items-center gap-3 group">
            
            <span class="font-medium text-sm text-slate-700 group-hover:text-black transition hidden sm:block">
              {{ userStore.user?.name || 'Jogador' }}
            </span>
            
            <Avatar 
                :key="avatarUrl" 
                class="cursor-pointer bg-slate-100 group-hover:ring-2 group-hover:ring-slate-200 transition"
            >
              <AvatarImage :src="avatarUrl" />
              <AvatarFallback class="bg-slate-200 text-slate-700 font-bold">
                {{ initials }}
              </AvatarFallback>
            </Avatar>

          </DropdownMenuTrigger>

          <DropdownMenuContent class="w-48 mr-4">
            <DropdownMenuItem @click="goTo('/profile')" class="cursor-pointer">
              👤 Profile
            </DropdownMenuItem>
            
            <DropdownMenuSeparator />
            
            <DropdownMenuItem @click="handleLogout" class="cursor-pointer text-red-600 focus:text-red-600">
              🚪 Logout
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

      </div>
    </div>
  </nav>
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

/* Estilo para link ativo (opcional, se quiseres destacar a página atual) */
.router-link-active {
  background: rgba(0,0,0,0.05);
  color: #000;
  font-weight: 600;
}
</style>