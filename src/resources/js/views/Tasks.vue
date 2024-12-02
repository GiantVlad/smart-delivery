<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTableBorder" title="Tasks" main>
      </SectionTitleLineWithButton>
      <BaseButtons>
        <BaseButton type="button" color="info" label="Create Task" @click="router.push('/task')"/>
      </BaseButtons>
      <BaseDivider />
      <NotificationBar color="info" :icon="mdiMonitorCellphone">
        <b>Resdevops ponsive table.</b> Collapses on mobile
      </NotificationBar>

      <CardBox class="mb-6 max-w-5xl" has-table v-if="tasks.length > 0">
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
                <div class="font-semibold text-center">Status</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-left">Courier</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-center">Count of orders</div>
              </th>
              <th class="p-2 whitespace-nowrap">
                <div class="font-semibold text-center">Date</div>
              </th>
            </tr>
            </thead>
            <!-- Table body -->
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60">
            <tr v-for="task in tasks" :key="task.id">
              <td class="p-2 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="font-medium text-gray-800 dark:text-gray-100">{{ task.id }}</div>
                </div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="font-medium text-gray-800 dark:text-gray-100">{{task.uuid}}</div>
                </div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left font-medium text-green-500">{{statuses[task.uuid]}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left">{{task.courierName}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left">{{task.countOrders}}</div>
              </td>
              <td class="p-2 whitespace-nowrap">
                <div class="text-left">{{task.updated_at}}</div>
              </td>
            </tr>
            </tbody>
          </table>
      </CardBox>

      <CardBox v-if="tasks.length < 1">
        <CardBoxComponentEmpty />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<script setup>

import { mdiMonitorCellphone, mdiTableBorder, mdiTableOff } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import NotificationBar from '@/components/NotificationBar.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import axios from "axios"
import { ref, onMounted, computed } from "vue"
import router from "@/router/index.js"
import BaseButton from "@/components/BaseButton.vue"
import BaseButtons from "@/components/BaseButtons.vue"
import BaseDivider from "@/components/BaseDivider.vue"
import {useTaskStatusStore} from "@/stores/taskStatus.js"

const tasks = ref([])
const taskStatusStore = useTaskStatusStore()

const statuses = computed(() => {
  const arr = []

  for (const [key, value] of Object.entries(taskStatusStore.tasks)) {
    arr.push({uuid: key, status: value});
  }

  return arr
})

onMounted(() => {
  axios.get('/api/tasks')
    .then((response) => {
      tasks.value = response.data.data
      for (const task of response.data.data) {
        taskStatusStore.updateStatus({
          uuid: task.uuid,
          status: task.status,
        })
      }
    })
})

</script>

