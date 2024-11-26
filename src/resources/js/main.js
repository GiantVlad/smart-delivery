import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Centrifugo from './centrifugo'
import App from './App.vue'
import router from './router'
import { useMainStore } from '@/stores/main.js'
import './css/main.css'

// Init Pinia
const pinia = createPinia()
// app.provide("centrifuge", centrifuge);
// Create Vue app
createApp(App)
  .use(router)
  .use(pinia)
  .use(
    Centrifugo,
    {
    url: "ws://centrifugo:8010/connection/websocket",
    token: "your-jwt-token", // Replace with the token received from your server ./centrifugo gentoken -u 123722
    })
  .mount('#app')

// Init main store
const mainStore = useMainStore(pinia)

// Dark mode
// Uncomment, if you'd like to restore persisted darkMode setting, or use `prefers-color-scheme: dark`. Make sure to uncomment localStorage block in src/stores/darkMode.js
// import { useDarkModeStore } from './stores/darkMode'

// const darkModeStore = useDarkModeStore(pinia)

// if (
//   (!localStorage['darkMode'] && window.matchMedia('(prefers-color-scheme: dark)').matches) ||
//   localStorage['darkMode'] === '1'
// ) {
//   darkModeStore.set(true)
// }

// Default title tag
const defaultDocumentTitle = 'Smart Delivery Service'

// Set document title from route meta
router.afterEach((to) => {
  document.title = to.meta?.title
    ? `${to.meta.title} — ${defaultDocumentTitle}`
    : defaultDocumentTitle
})
