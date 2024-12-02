import { defineStore } from 'pinia'
import { reactive } from 'vue'

export const useOrderStatusStore = defineStore('orderStatus', () => {
  const orders = reactive({})

  function updateOrderStatus(payload) {
    if (payload.order && payload.status) {
      orders[payload.order] = payload.status
    }
  }

  return {
    orders,
    updateOrderStatus,
  }
})
