import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from './pages/Dashboard.vue'
import Orders from "./pages/Orders.vue";
import Tasks from "./pages/Tasks.vue";

const routerHistory = createWebHistory()

const router = createRouter({
  history: routerHistory,
  routes: [
    {
      path: '/',
      component: Dashboard
    },
    {
      path: '/orders',
      component: Orders
    },
    {
      path: '/tasks',
      component: Tasks
    },
  ]
})

export default router
