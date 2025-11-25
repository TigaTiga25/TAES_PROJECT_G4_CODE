import { createRouter, createWebHistory } from 'vue-router'

import LoginPage from '@/pages/login/LoginPage.vue'
import HomePage from '@/pages/home/HomePage.vue'
import GameBoard from '@/pages/gameBoard/GameBoard.vue'
import RegisterPage from '@/pages/register/RegisterPage.vue'
import GameHistory from '@/pages/gameHistory/GameHistory.vue'
import AboutPage from '@/pages/about/AboutPage.vue' 
import Transactions from '@/pages/transactions/Transactions.vue' 

const routes = [
  {
    path: '/',
    name: 'Login',
    component: LoginPage
  },
  {
      path: '/transactions',
      name: 'transactions',
      component: Transactions
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
    path: '/gameBoard/:id',
    name: 'GameBoard',
    component: GameBoard,
    props: route => ({ id: parseInt(route.params.id) })
  },
  {
    path: '/history',
    name: 'GameHistory',
    component: GameHistory
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
