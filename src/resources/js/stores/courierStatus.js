import { defineStore } from 'pinia'
import { reactive } from 'vue'

export const useCourierStatusStore = defineStore('courierStatus', () => {
  const couriers = reactive({})

  function updateStatus(payload) {
    if (payload.uuid && payload.status) {
      couriers[payload.uuid] = payload.status
    }
  }

  return {
    couriers,
    updateStatus,
  }
})
