<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTableBorder" title="Edit route" main>
      </SectionTitleLineWithButton>
      <NotificationBar color="info" :icon="mdiMonitorCellphone">
        <b>Responsive table.</b> Collapses on mobile
      </NotificationBar>

      <CardBox class="mb-6" has-table>
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
        <CardBox />
        <NotificationBar color="danger" v-if="error && selectedTask">
          {{error}}
        </NotificationBar>
        <CardBox class="mb-6" has-table form @submit.prevent="submit" :is-form="true" v-if="selectedTask !== null">
          <table class="table-auto w-full">
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
          <VueDraggableNext class="dragArea list-group w-full text-sm divide-y divide-gray-100 dark:divide-gray-700/60"
                            :list="points"
                            @change="orderPoints"
                            tag="tbody"
          >
          <tr v-for="(point, idx) in points" :key="idx">
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{idx}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div :class="[
                'text-left',
                 (idx === 0 && invalidFirstRoute) || (idx === (points.length - 1) && invalidLastRoute) ? 'text-red-500' : '',
              ]">{{point.pointAddress}}</div>
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
import NotificationBarInCard from "@/components/NotificationBarInCard.vue";


const selectedTask = ref(null)
const tasks = ref([])
const orders = ref([])
const points = ref([])
const error = ref(null)

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

let orderStartPoints = []
let orderEndPoints = []

const getOrders = () => {
  axios.get('/api/orders-by-task/' + selectedTask.value)
    .then((response) => {
      orderStartPoints = orderEndPoints = orders.value = response.data.data
      orderStartPoints = orderStartPoints.map(el => el.startPointId)
      orderEndPoints = orderEndPoints.map(el => el.endPointId)
    })
}

const getRoute = () => {
  axios.get('/api/route/' + selectedTask.value)
    .then((response) => {
      points.value = response.data.data
    })
}

const invalidFirstRoute = ref(false)
const invalidLastRoute =  ref(false)

const orderPoints = () => {
  invalidFirstRoute.value = false
  invalidLastRoute.value = false
  if (! orderStartPoints.includes(points.value[0].pointId)) {
    invalidFirstRoute.value = true
  }
  if (! orderEndPoints.includes(points.value[points.value.length-1].pointId)) {
    invalidLastRoute.value = true
  }
  points.value.map((point, idx) => point.sequence = idx)
}

const form = reactive({
  taskUuid: null,
  points: [],
})

const submit = () => {
  axios.post('/api/update-route',
    {
      taskUuid: form.taskUuid,
      points: [...new Set(points.value.map(el => el.pointId))]
    })
    .then(response => {
      error.value = 0
    }).catch(e => {
      console.log(e)
      error.value = e.response.data.message
  })
}

</script>
