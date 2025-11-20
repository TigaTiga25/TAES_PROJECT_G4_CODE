import { createRouter, createWebHistory } from 'vue-router'

import LoginPage from '@/pages/login/LoginPage.vue'
import HomePage from '@/pages/home/HomePage.vue'
import GameBoard from '@/pages/gameBoard/GameBoard.vue'
import RegisterPage from '@/pages/register/RegisterPage.vue'
import AboutPage from '@/pages/about/AboutPage.vue' // ← cria esta página (já explico abaixo)

const routes = [
  {
    path: '/',
    name: 'Login',
    component: LoginPage
  },
  {
  path: '/profile',
  name: 'profile',
  component: () => import('@/pages/profile/ProfilePage.vue')
  },
  {
    path: '/home',
    name: 'Home',
    component: HomePage
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterPage
  },
  {
    path: '/gameBoard',
    name: 'GameBoard',
    component: GameBoard
  },
  {
    path: '/about',
    name: 'About',
    component: AboutPage
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router
