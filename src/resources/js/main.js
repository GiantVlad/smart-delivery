import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createCentrifuge } from './centrifugo'
import App from './App.vue'
import router from './router'
import { useMainStore } from '@/stores/main.js'
import './css/main.css'

// Init Pinia
const pinia = createPinia()
const centrifuge = createCentrifuge({
  url: "wss://delivery.cloud-workflow.com/connection/websocket",
  token: "your-jwt-token",
});

// Provide Centrifugo globally

// app.provide("centrifuge", centrifuge);
// Create Vue app
const app = createApp(App)
app.use(router)
  .use(pinia)
  .mount('#app')

app.provide('centrifuge', centrifuge);
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
