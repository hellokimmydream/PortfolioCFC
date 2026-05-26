import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import Projets from '../views/Projet.vue'
import Theories from '@/views/Theorie.vue'
import Modules from '@/views/Modules.vue'
import Accueil from '@/views/Accueil.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    { path: '/projets', name: 'projets', component: Projets },
    { path: '/theories', name: 'theories', component: Theories },
    { path: '/modules', name: 'modules', component: Modules },
    { path: '/', name: 'accueil', component: Accueil },
  ],
})

export default router
