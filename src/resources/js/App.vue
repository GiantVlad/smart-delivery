<script setup>
import { RouterView } from 'vue-router'
import {inject, onMounted, onUnmounted} from "vue"
import { useOrderStatusStore } from "@/stores/orderStatus.js"
import { useTaskStatusStore } from "@/stores/taskStatus.js"
import { useCourierStatusStore } from "@/stores/courierStatus.js"

const centrifugo = inject('centrifugo')
const sub = [];

onUnmounted(() => {
  sub.forEach(el => el.unsubscribe())
});

onMounted(async () => {
  if (centrifugo) {
    const subTest = centrifugo.subscribe('test', data => {
      console.log(data);
    });
    sub.push(subTest)

    const subOrderStatus = centrifugo.subscribe('order_status', data => {
      const orderStatusStore = useOrderStatusStore()
      orderStatusStore.updateOrderStatus(data)
    });
    sub.push(subOrderStatus)

    const subTaskStatus = centrifugo.subscribe('task_status', data => {
      const taskStatusStore = useTaskStatusStore()
      taskStatusStore.updateStatus(data)
    });
    sub.push(subTaskStatus)

    const subCourierStatus = centrifugo.subscribe('courier_status', data => {
      const courierStatusStore = useCourierStatusStore()
      courierStatusStore.updateStatus(data)
    });
    sub.push(subCourierStatus)
  } else {
    console.error('Centrifuge instance is invalid or subscribe method is missing.')
  }
})
</script>

<template>
  <RouterView />
</template>
