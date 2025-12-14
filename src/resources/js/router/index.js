import { createRouter, createWebHashHistory } from 'vue-router'
import Style from '@/views/StyleView.vue'
import Home from '@/views/HomeView.vue'
import Login from '@/views/LoginView.vue'
import Users from '@/views/Users.vue'
import { useMainStore } from "@/stores/main.js"

const routes = [
  // {
  //   meta: {
  //     title: 'Select style',
  //   },
  //   path: '/',
  //   name: 'style',
  //   component: Style
  // },
  {
    meta: {
      title: 'Login',
      requiresAuth: false // Explicitly set to false for login
    },
    path: '/login',
    name: 'login',
    component: Login
  },
  {
    meta: {
      title: 'Users',
      requiresAuth: true,
    },
    path: '/users',
    name: 'users',
    component: Users
  },
  {
    // Document title tag
    // We combine it with defaultDocumentTitle set in `src/main.js` on router.afterEach hook
    meta: {
      title: 'Dashboard',
    },
    path: '/',
    name: 'dashboard',
    component: Home
  },
  {
    meta: {
      title: 'Orders',
      requiresAuth: true,
    },
    path: '/orders',
    name: 'orders',
    component: () => import('@/views/Orders.vue')
  },
  {
    meta: {
      title: 'Create order',
      requiresAuth: true,
    },
    path: '/order',
    name: 'order-create',
    component: () => import('@/views/OrderCreateFormView.vue')
  },
  {
    meta: {
      title: 'Tasks',
      requiresAuth: true,
    },
    path: '/tasks',
    name: 'tasks',
    component: () => import('@/views/Tasks.vue')
  },
  {
    meta: {
      title: 'Create task',
      requiresAuth: true,
    },
    path: '/task',
    name: 'task-create',
    component: () => import('@/views/TaskCreateFormView.vue')
  },
  {
    meta: {
      title: 'Update order in task',
      requiresAuth: true,
    },
    path: '/update-order-in-task',
    name: 'update-order-in-task',
    component: () => import('@/views/UpdateOrderInTaskView.vue')
  },
  {
    meta: {
      title: 'Edit route',
      requiresAuth: true,
    },
    path: '/edit-route',
    name: 'edit-route',
    component: () => import('@/views/EditRouteView.vue')
  },
  {
    meta: {
      title: 'Couriers',
      requiresAuth: true,
    },
    path: '/couriers',
    name: 'couriers',
    component: () => import('@/views/Couriers.vue')
  },
  {
    meta: {
      title: 'Courier\'s working hours',
      requiresAuth: true
    },
    path: '/courier-working-hours/:courierId(\\d+)',
    name: 'courier-working-hours',
    component: () => import('@/views/CourierWorkingHours.vue'),
    props: true
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { top: 0 }
  }
})

router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth) {
    const mainStore = useMainStore()
    if (mainStore.isAuthenticated) {
      // User is authenticated, proceed to the route
      next()
    } else {
      // User is not authenticated, redirect to login
      next('/login')
    }
  } else {
    // Non-protected route, allow access
    next();
  }
})


export default router
