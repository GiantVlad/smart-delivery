import { createRouter, createWebHashHistory } from 'vue-router'
import Style from '@/views/StyleView.vue'
import Home from '@/views/HomeView.vue'

const routes = [
  {
    meta: {
      title: 'Select style'
    },
    path: '/',
    name: 'style',
    component: Style
  },
  {
    // Document title tag
    // We combine it with defaultDocumentTitle set in `src/main.js` on router.afterEach hook
    meta: {
      title: 'Dashboard'
    },
    path: '/dashboard',
    name: 'dashboard',
    component: Home
  },
  {
    meta: {
      title: 'Orders'
    },
    path: '/orders',
    name: 'orders',
    component: () => import('@/views/Orders.vue')
  },
  {
    meta: {
      title: 'Forms'
    },
    path: '/tasks',
    name: 'tasks',
    component: () => import('@/views/Tasks.vue')
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { top: 0 }
  }
})

export default router
