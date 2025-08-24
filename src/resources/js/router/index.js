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
      title: 'Login'
    },
    path: '/login',
    name: 'login',
    component: Login
  },
  {
    meta: {
      title: 'Users',
      requiresAuth: true
    },
    path: '/users',
    name: 'users',
    component: Users
  },
  {
    // Document title tag
    // We combine it with defaultDocumentTitle set in `src/main.js` on router.afterEach hook
    meta: {
      title: 'Dashboard'
    },
    path: '/',
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
    component: () => import('@/views/Couriers.vue')
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
