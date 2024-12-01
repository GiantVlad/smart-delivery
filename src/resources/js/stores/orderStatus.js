import { defineStore } from 'pinia'
import { reactive } from 'vue'

export const useOrderStatusStore = defineStore('orderStatus', () => {
  const order = reactive({})

  function updateOrderStatus(payload) {
    if (payload.order && payload.status) {
      order[payload.order] = payload.status
    }
  }

  return {
    order,
    updateOrderStatus,
  }
})
