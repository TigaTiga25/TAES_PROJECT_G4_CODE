import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '@/pages/login/LoginPage.vue'
import HomePage from '@/pages/home/HomePage.vue'

const routes = [
  {
    path: '/',
    name: 'Login',
    component: LoginPage
  },
  {
    path: '/home',
    name: 'Home',
    component: HomePage
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router
