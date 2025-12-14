import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

// Helper function to load state from localStorage
const loadState = () => {
  try {
    const serializedState = localStorage.getItem('mainStore')
    if (serializedState === null) {
      return undefined
    }
    return JSON.parse(serializedState)
  } catch (err) {
    console.error('Failed to load state:', err)
    return undefined
  }
}

// Helper function to save state to localStorage
const saveState = (state) => {
  try {
    const serializedState = JSON.stringify(state)
    localStorage.setItem('mainStore', serializedState)
  } catch (err) {
    console.error('Failed to save state:', err)
  }
}

export const useMainStore = defineStore('main', () => {
  const initialState = loadState()

  const userName = ref(initialState?.userName || '')
  const userEmail = ref(initialState?.userEmail || '')
  const isAuthenticated = ref(initialState?.isAuthenticated || false)

  const userAvatar = computed(
    () =>
      `https://api.dicebear.com/7.x/avataaars/svg?seed=${userEmail.value.replace(
        /[^a-z0-9]+/gi,
        '-'
      )}`
  )

  const isFieldFocusRegistered = ref(false)

  function setUser(payload) {
    if (payload.name) {
      userName.value = payload.name
    }
    if (payload.email) {
      userEmail.value = payload.email
    }
    isAuthenticated.value = userEmail.value && userName.value
  }

  watch (
    [userName, userEmail, isAuthenticated],
    () => {
      saveState({
        userName: userName.value,
        userEmail: userEmail.value,
        isAuthenticated: isAuthenticated.value
      })
    },
    { deep: true }
  )

  // Clear the stored state (useful for logout)
  function clearStore() {
    userName.value = ''
    userEmail.value = ''
    isAuthenticated.value = false
    localStorage.removeItem('mainStore')
  }

  return {
    userName,
    userEmail,
    userAvatar,
    isFieldFocusRegistered,
    isAuthenticated,
    setUser,
    clearStore,
  }
})
