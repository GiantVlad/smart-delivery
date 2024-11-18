<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTableBorder" title="Edit route" main>
      </SectionTitleLineWithButton>
      <NotificationBar color="info" :icon="mdiMonitorCellphone">
        <b>Responsive table.</b> Collapses on mobile
      </NotificationBar>

      <CardBox class="mb-6" has-table form @submit.prevent="submit" :is-form="true">
        <FormField label="Task">
          <FormControl v-model="selectedTask" :options="tasks"/>
        </FormField>
        <BaseDivider />
        <table class="table-auto w-full" v-if="selectedTask !== null">
          <!-- Table header -->
          <thead class="text-xs font-semibold uppercase dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50">
          <tr>
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
              <div class="font-semibold text-left">Pick up</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-center">Destination</div>
            </th>
          </tr>
          </thead>
          <!-- Table body -->
          <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60">
          <tr v-for="order in orders" :key="order.id">
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
              <div class="text-left">{{order.startPointAddress}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{order.endPointAddress}}</div>
            </td>
          </tr>
          </tbody>
        </table>
        <BaseDivider />
        <table class="table-auto w-full" v-if="selectedTask !== null">
          <!-- Table header -->
          <thead class="text-xs font-semibold uppercase dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50">
          <tr>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">#</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Point</div>
            </th>
          </tr>
          </thead>
          <!-- Table body -->
          <VueDraggableNext class="dragArea list-group w-full text-sm divide-y divide-gray-100 dark:divide-gray-700/60" :list="orders" @change="log" tag="tbody">
          <tr v-for="(point, idx) in points" :key="idx">
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{idx}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{point.startAddress}}</div>
            </td>
          </tr>
          </VueDraggableNext>
        </table>
        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Save route" />
          </BaseButtons>
        </template>
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
import { VueDraggableNext } from 'vue-draggable-next'
import axios from "axios";
import {ref, onMounted, watch, reactive} from "vue";
import FormControl from "@/components/FormControl.vue";
import FormField from "@/components/FormField.vue";
import BaseDivider from "@/components/BaseDivider.vue";
import BaseButtons from "@/components/BaseButtons.vue";
import BaseButton from "@/components/BaseButton.vue";


const selectedTask = ref(null)
const tasks = ref([])
const orders = ref([])
const points = ref([])

onMounted(() => {
  axios.get('/api/tasks')
    .then((response) => {
      tasks.value = response.data.data.map(el => ({id: el.uuid, label: el.uuid + ': ' + el.courierName}))
    })
})


watch(selectedTask, async (newTask, oldTask) => {
  if (newTask !== null && (newTask !== oldTask)) {
    getOrders()
    getRoute()
    form.taskUuid = newTask
  }
})

const getOrders = () => {
  axios.get('/api/orders-by-task/' + selectedTask.value)
    .then((response) => {
      orders.value = response.data.data
    })
}

const getRoute = () => {
  axios.get('/api/route/' + selectedTask.value)
    .then((response) => {
      points.value = response.data.data
    })
}

const orderPoints = () => {
  points.value = []
  form.points = []
  let next = {startAddress: null, endAddress: null}
  orders.value.forEach((order, idx) => {
    if (next.startAddress !== null) {
      next.endAddress = order.startPointAddress
      points.value.push(next)
    } else {
      form.points.push(order.startPointId)
    }
    form.points.push(order.endPointId)
    points.value.push(
      {startAddress: order.startPointAddress, endAddress: order.endPointAddress}
    )
    if ((idx+1) === orders.value.length) {
      return
    }
    next.startAddress = order.endPointAddress
  })
}

const log = () => ''

const form = reactive({
  taskUuid: null,
  points: [],
})

const submit = () => {
  console.log(form)
  axios.post('/api/update-route',
    {
      taskUuid: form.taskUuid,
      points: [...new Set(form.points)]
    })
    .then((response) => {
      console.log('updated')
    })
}

</script>
