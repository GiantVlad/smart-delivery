import { defineStore } from 'pinia'
import { reactive } from 'vue'

export const useOrderStatusStore = defineStore('orderStatus', () => {
  const order = reactive({})

  function setOrderStatus(payload) {
    if (payload.order && payload.status) {
      order[payload.order].value = payload.status
    }
  }

  return {
    order,
    setOrderStatus,
  }
})
