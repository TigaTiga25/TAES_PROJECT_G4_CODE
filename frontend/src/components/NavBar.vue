<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { userStore } from '@/stores/userStore.js'
import axios from 'axios' // <--- Importante para falar com o teu Controller
import { Bell } from 'lucide-vue-next' // Certifica-te que tens este ícone

// --- UI Components Imports ---
import {
  NavigationMenu,
  NavigationMenuList,
  NavigationMenuItem
} from '@/components/ui/navigation-menu'

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'

// --- Logic ---
const router = useRouter()
const route = useRoute()

// Variáveis Locais para as Notificações
const notifications = ref([])
let pollingInterval = null

// Lógica de Visibilidade da Navbar
const showNavbar = computed(() => {
  const hiddenRoutes = ['/', '/login', '/register'];
  return !hiddenRoutes.some(r => route.path === r || route.path.startsWith('/gameBoard/'));
});

// Lógica do Avatar (Mantém a tua)
const avatarUrl = computed(() => {
  const u = userStore.user
  const seed = u?.custom_avatar_seed || u?.name || 'Player'
  return `https://api.dicebear.com/7.x/avataaars/svg?seed=${seed}`
})

const initials = computed(() => {
  const u = userStore.user
  return u?.name ? u.name[0].toUpperCase() : '?'
})

// --- LÓGICA DE NOTIFICAÇÕES (Sem Pinia) ---

// 1. Buscar notificações ao teu Controller Laravel
const fetchNotifications = async () => {
  if (!userStore.isLoggedIn) return;

  try {
    const response = await axios.get('/api/notifications/unread')

    // Se houver novas notificações, atualizamos a lista
    if (response.data) {
      const novas = response.data;

      // Deteta se chegou algo novo para mandar o alerta do browser (System-Level)
      // Compara pelo ID ou tamanho da lista
      if (novas.length > notifications.value.length) {
         const last = novas[0]; // A mais recente
         if (Notification.permission === "granted") {
            new Notification(last.title, { body: last.message });
         }
      }

      notifications.value = novas;
    }
  } catch (error) {
    console.error("Erro notifications:", error);
  }
}

// 2. Marcar como lida e navegar
const handleNotificationClick = async (notif) => {
  try {
    // Avisa o backend (Lógica do teu Controller)
    await axios.post(`/api/notifications/${notif.id}/read`);

    // Remove da lista visualmente
    notifications.value = notifications.value.filter(n => n.id !== notif.id);

    // Navega para o ecrã certo
    if (notif.type === 'LEADERBOARD') router.push('/scoreboards');
    else if (notif.type === 'CUSTOMIZATION') router.push('/customizations');

  } catch (e) {
    console.error(e);
  }
}

// 3. Iniciar o ciclo quando a Navbar aparece
onMounted(() => {
  // Pede permissão ao browser para notificações nativas
  if ('Notification' in window && Notification.permission !== 'granted') {
    Notification.requestPermission();
  }

  fetchNotifications(); // Chama já uma vez
  pollingInterval = setInterval(fetchNotifications, 5000); // Repete a cada 5s
})

onUnmounted(() => {
  clearInterval(pollingInterval);
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
        </NavigationMenuList>
       </NavigationMenu>

      <div class="flex items-center gap-4">

        <template v-if="userStore.isLoggedIn">

            <Badge variant="secondary" class="px-3 py-1 text-sm cursor-pointer hover:bg-slate-200 transition" @click="goTo('/transactions')">
              🪙 {{ userStore.user?.coins_balance || 0 }}
            </Badge>

            <DropdownMenu>
              <DropdownMenuTrigger class="relative focus:outline-none p-2 rounded-full hover:bg-slate-100 transition cursor-pointer">
                  <Bell class="w-5 h-5 text-slate-700" />
                  <span v-if="notifications.length > 0"
                        class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full transform translate-x-1/4 -translate-y-1/4">
                    {{ notifications.length }}
                  </span>
              </DropdownMenuTrigger>

              <DropdownMenuContent class="w-80 mr-8" align="end">
                <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <div v-if="notifications.length === 0" class="p-4 text-sm text-slate-500 text-center">
                  No new notifications.
                </div>

                <DropdownMenuItem
                  v-for="notif in notifications"
                  :key="notif.id"
                  @click="handleNotificationClick(notif)"
                  class="cursor-pointer flex flex-col items-start gap-1 p-3 focus:bg-slate-50 border-b last:border-0"
                >
                  <div class="flex items-center gap-2 w-full font-semibold text-sm">
                    <span v-if="notif.type === 'LEADERBOARD'">🏆</span>
                    <span v-else>🎨</span>
                    {{ notif.title }}
                  </div>
                  <p class="text-xs text-slate-600">{{ notif.message }}</p>
                  <span class="text-[10px] text-slate-400 self-end mt-1">Click to view</span>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

            <DropdownMenu>
              <DropdownMenuTrigger class="focus:outline-none flex items-center gap-3 group">
                <span class="font-medium text-sm text-slate-700 group-hover:text-black transition hidden sm:block">
                  {{ userStore.user?.name || 'Player' }}
                </span>
                <Avatar class="cursor-pointer bg-slate-100 group-hover:ring-2 group-hover:ring-slate-200 transition">
                  <AvatarImage :src="avatarUrl" class="object-cover" />
                  <AvatarFallback>{{ initials }}</AvatarFallback>
                </Avatar>
              </DropdownMenuTrigger>
              <DropdownMenuContent class="w-48 mr-4">
                <DropdownMenuItem @click="goTo('/profile')">👤 Profile</DropdownMenuItem>
                <DropdownMenuItem @click="handleLogout" class="text-red-600">🚪 Logout</DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

        </template>

        <NavigationMenu v-else>
          <NavigationMenuList>
            <NavigationMenuItem>
              <RouterLink to="/" class="nav-link">Login</RouterLink>
            </NavigationMenuItem>
          </NavigationMenuList>
        </NavigationMenu>

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
.router-link-active {
  background: rgba(0,0,0,0.05);
  color: #000;
  font-weight: 600;
}
</style>
