import { defineStore } from 'pinia'
import { reactive } from 'vue'

export const useTaskStatusStore = defineStore('taskStatus', () => {
  const tasks = reactive({})

  function updateStatus(payload) {
    if (payload.uuid && payload.status) {
      tasks[payload.uuid] = payload.status
    }
  }

  return {
    tasks,
    updateStatus,
  }
})
