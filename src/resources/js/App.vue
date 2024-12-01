<script setup>
import { RouterView } from 'vue-router'
import { inject, onMounted } from "vue"
import { useOrderStatusStore } from "@/stores/orderStatus.js";

const centrifuge = inject('centrifuge')
console.log(centrifuge)
onMounted(() => {
  if (centrifuge) {
    const sub = centrifuge.newSubscription('order_status')

    sub.on('publication', function(ctx) {
      console.log(ctx.data)
      const orderStatusStore = useOrderStatusStore()
      orderStatusStore.setOrderStatus(ctx.data)
    })

    sub.subscribe()
  } else {
    console.error('Centrifuge instance is invalid or subscribe method is missing.')
  }
})
</script>

<template>
  <RouterView />
</template>
