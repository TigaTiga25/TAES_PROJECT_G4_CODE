import { createRouter, createWebHistory } from 'vue-router'
import { userStore } from '@/stores/userStore.js'

import LoginPage from '@/pages/login/LoginPage.vue'
import HomePage from '@/pages/home/HomePage.vue'
import GameBoard from '@/pages/gameBoard/GameBoard.vue'
import RegisterPage from '@/pages/register/RegisterPage.vue'
import GameHistory from '@/pages/gameHistory/GameHistory.vue'
import AboutPage from '@/pages/about/AboutPage.vue'
import Transactions from '@/pages/transactions/TransactionsPage.vue'
import ScoreboardsPage from '@/pages/scoreboard/ScoreboardPage.vue'
import Customizations from '@/pages/customizations/CustomizationsPage.vue'

const routes = [
  // --- ROTAS PÚBLICAS (Qualquer um entra) ---
  {
    path: '/',
    name: 'Login',
    component: LoginPage
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterPage
  },
  {
    path: '/about',
    name: 'About',
    component: AboutPage
  },

  // --- ROTAS PRIVADAS (Requer Login) ---
  {
      path: '/transactions',
      name: 'transactions',
      component: Transactions,
      meta: { requiresAuth: true }
  },
  {
      path: '/customizations',
      name: 'customizations',
      component: Customizations,
      meta: { requiresAuth: true }
    },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/pages/profile/ProfilePage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/home',
    name: 'Home',
    component: HomePage,
    meta: { requiresAuth: true }
  },
  {
    path: '/gameBoard/:type/:id',
    name: 'GameBoard',
    component: GameBoard,
    props: route => ({
      type: parseInt(route.params.type),
      id: parseInt(route.params.id)
    }),
    meta: { requiresAuth: true }
  },
  {
    path: '/history',
    name: 'GameHistory',
    component: GameHistory,
    meta: { requiresAuth: true }
  },
  {
    path: '/scoreboards',
    name: 'Scoreboards',
    component: ScoreboardsPage,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

//Route Guard
router.beforeEach((to, from, next) => {
    // Verifica se a rota para onde vais exige autenticação
    if (to.meta.requiresAuth && !userStore.user) {
        // Se exige e NÃO tens user na store -> Manda para o Login
        next('/')
    } else {
        // Caso contrário -> Deixa passar
        next()
    }
})

export default router
