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
      title: 'Create order'
    },
    path: '/order',
    name: 'order-create',
    component: () => import('@/views/OrderCreateFormView.vue')
  },
  {
    meta: {
      title: 'Tasks'
    },
    path: '/tasks',
    name: 'tasks',
    component: () => import('@/views/Tasks.vue')
  },
  {
    meta: {
      title: 'Create task'
    },
    path: '/task',
    name: 'task-create',
    component: () => import('@/views/TaskCreateFormView.vue')
  },
  {
    meta: {
      title: 'Update order in task'
    },
    path: '/update-order-in-task',
    name: 'update-order-in-task',
    component: () => import('@/views/UpdateOrderInTaskView.vue')
  },
  {
    meta: {
      title: 'Edit route'
    },
    path: '/edit-route',
    name: 'edit-route',
    component: () => import('@/views/EditRouteView.vue')
  },
  {
    meta: {
      title: 'Couriers'
    },
    path: '/couriers',
    name: 'couriers',
    component: () => import('@/views/CouriersView.vue')
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
