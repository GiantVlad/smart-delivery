<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTableBorder" title="Orders" main>
      </SectionTitleLineWithButton>
      <BaseButtons>
        <BaseButton type="button" color="info" label="Create Order" @click="router.push('/order')"/>
      </BaseButtons>
      <BaseDivider />
      <NotificationBar color="info" :icon="mdiMonitorCellphone">
        <b>Responsive table.</b> Collapses on mobile
      </NotificationBar>
      <CardBox class="mb-6 max-w-5xl" has-table v-if="orders.length > 0">
        <!-- Table -->
          <table class="table-auto w-full">
            <!-- Table header -->
            <thead class="text-xs font-semibold uppercase dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50">
            <tr>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-left">#</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-left">UUID</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-left">Type</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-center">Status</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-left">Customer</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-left">Courier</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-left">Pick up</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-center">Destination</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-center">Date</div>
              </th>
            </tr>
            </thead>
            <!-- Table body -->
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60">
            <tr v-for="order in orders" :key="order.id">
              <td class="p-2 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="font-medium text-gray-800 dark:text-gray-100">{{ order.id }}</div>
                </div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="font-medium text-gray-800 dark:text-gray-100">{{order.uuid}}</div>
                </div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left">{{order.unitType}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left font-medium text-green-500">{{order.status}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{order.customerEmail}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left">{{order.taskCourierName}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left">{{order.startPointAddress}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left">{{order.endPointAddress}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left">{{order.updated_at}}</div>
              </td>
            </tr>
            </tbody>
          </table>
      </CardBox>

      <CardBox v-if="orders.length < 1">
        <CardBoxComponentEmpty />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<script setup>

import { mdiMonitorCellphone, mdiTableBorder } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import NotificationBar from '@/components/NotificationBar.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import axios from "axios";
import {ref, onMounted, inject} from "vue";
import BaseButton from "@/components/BaseButton.vue";
import BaseButtons from "@/components/BaseButtons.vue";
import router from "@/router/index.js";
import BaseDivider from "@/components/BaseDivider.vue";

let orders = ref([])
const centrifuge = inject('centrifuge');

onMounted(() => {
  axios.get('/api/orders')
    .then((response) => {
      orders.value = response.data.data
    })
  centrifuge.subscribe('order_status', (ctx) => {
    console.log('New message:', ctx.data);
    // messages.value.push(ctx.data);
  });
})

</script>
