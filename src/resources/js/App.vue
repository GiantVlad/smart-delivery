<script setup>
import { RouterView } from 'vue-router'
import {inject, onMounted} from "vue";

const centrifuge = inject('centrifuge');

onMounted(() => {
  if (centrifuge && typeof centrifuge.subscribe === 'function') {
    const sub = centrifuge.newSubscription('order_status')

    // React on `news` channel real-time publications.
    sub.on('publication', function(ctx) {
      console.log(ctx.data);
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
