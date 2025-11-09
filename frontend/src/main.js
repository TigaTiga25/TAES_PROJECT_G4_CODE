import { createApp } from 'vue'
//import { createPinia } from 'pinia'
//import App from './App.vue'
//import router from './router'

import AboutPage from '@/pages/about/AboutPage.vue'
const app = createApp(AboutPage)

//app.use(createPinia())
//app.use(router)

app.mount('#app')
