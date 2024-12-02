<script setup>
import { RouterView } from 'vue-router'
import { inject, onMounted } from "vue"
import { useOrderStatusStore } from "@/stores/orderStatus.js"
import { useTaskStatusStore } from "@/stores/taskStatus.js"
import { useCourierStatusStore } from "@/stores/courierStatus.js"

const centrifuge = inject('centrifuge')

onMounted(() => {
  if (centrifuge) {
    const subOrderStatus = centrifuge.newSubscription('order_status')
    subOrderStatus.on('publication', function(ctx) {
      const orderStatusStore = useOrderStatusStore()
      orderStatusStore.updateOrderStatus(ctx.data)
    })
    subOrderStatus.subscribe()

    const subTaskStatus = centrifuge.newSubscription('task_status')
    subTaskStatus.on('publication', function(ctx) {
      const taskStatusStore = useTaskStatusStore()
      taskStatusStore.updateStatus(ctx.data)
    })
    subTaskStatus.subscribe()

    const subCourierStatus = centrifuge.newSubscription('courier_status')
    subCourierStatus.on('publication', function(ctx) {
      const courierStatusStore = useCourierStatusStore()
      courierStatusStore.updateStatus(ctx.data)
    })
    subCourierStatus.subscribe()
  } else {
    console.error('Centrifuge instance is invalid or subscribe method is missing.')
  }
})
</script>

<template>
  <RouterView />
</template>
