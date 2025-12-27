import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createCentrifuge } from './centrifugo'
import App from './App.vue'
import router from './router'
import { useMainStore } from '@/stores/main.js'
import './css/main.css'
// Dark mode
// Uncomment, if you'd like to restore persisted darkMode setting, or use `prefers-color-scheme: dark`. Make sure to uncomment localStorage block in src/stores/darkMode.js
import { useDarkModeStore } from './stores/darkMode'
import CentrifugoPlugin from "@/plugins/centrifugo.js"


// Init Pinia
const pinia = createPinia()

// Create Vue app
const app = createApp(App)

// Create and provide Centrifugo instance
// const centrifugo = createCentrifuge({
//   url: process.env.VITE_CENTRIFUGO_WS_URL || 'ws://localhost:8010/connection/websocket',
//   token: process.env.VITE_CENTRIFUGO_TOKEN || ''
// });

// Make centrifugo available in the app
// app.config.globalProperties.$centrifugo = centrifugo;
// app.provide('centrifuge', centrifugo);

// Use Pinia
//app.provide('centrifuge', centrifuge);
app.use(router)
  .use(pinia)
  .use(CentrifugoPlugin, {
    url: import.meta.env.VITE_CENTRIFUGO_WS_URL
      || 'ws://localhost:8010/connection/websocket',
  })
  .mount('#app')

// Init main store
const mainStore = useMainStore(pinia)

const darkModeStore = useDarkModeStore(pinia)

if ((!localStorage['darkMode'] && window.matchMedia('(prefers-color-scheme: dark)').matches) ||
  localStorage['darkMode'] === '1'
) {
  darkModeStore.set(true)
}

// Default title tag
const defaultDocumentTitle = 'Smart Delivery Service'

// Set document title from route meta
router.afterEach((to) => {
  document.title = to.meta?.title
    ? `${to.meta.title} — ${defaultDocumentTitle}`
    : defaultDocumentTitle
})
