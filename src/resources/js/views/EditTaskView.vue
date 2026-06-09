<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTableBorder" title="Edit Task" main>
      </SectionTitleLineWithButton>
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
              <div class="font-semibold text-left">Order ID</div>
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
                <div class="font-medium text-gray-800 dark:text-gray-100">{{order.id}}</div>
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
      </CardBox>
      <CardBox />
        <NotificationBar color="danger" v-if="error && selectedTask">
          {{error}}
        </NotificationBar>
        <NotificationBar color="warning" v-if="selectedTask && !isTaskEditable">
          This task has status "{{taskStatus}}" and cannot be edited.
        </NotificationBar>
        <NotificationBar color="danger" v-if="invalidOrderSequence">
          Invalid route: pickup point must come before delivery point for each order.
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
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Type</div>
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
              <div class="text-left">{{ idx + 1 }}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div :class="[
                'text-left',
                 (idx === 0 && invalidFirstRoute) || (idx === (points.length - 1) && invalidLastRoute) ? 'text-red-500' : '',
              ]">{{point.pointAddress}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{point.type}}</div>
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
      <CardBox class="mb-6" v-if="selectedTask !== null">
        <div class="mb-3">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Route map</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Markers follow the current pickup and delivery sequence.
          </p>
        </div>
        <GoogleMap
          v-if="googleMapsApiKey && mapPoints.length"
          :api-key="googleMapsApiKey"
          class="rounded-xl overflow-hidden"
          style="width: 100%; height: 420px"
          :center="mapCenter"
          :zoom="12"
        >
          <Polyline
            v-if="routePath.length > 1"
            :options="routeLineOptions"
          />
          <Marker
            v-for="(point, idx) in mapPoints"
            :key="`${point.pointId}-${idx}`"
            :options="{
              position: point.position,
              label: String(idx + 1),
              title: `${idx + 1}. ${point.type}: ${point.pointAddress}`,
            }"
          />
        </GoogleMap>
        <NotificationBar v-else-if="!googleMapsApiKey" color="warning">
          Set VITE_GOOGLE_MAPS_API_KEY to show the route map.
        </NotificationBar>
        <NotificationBar v-else color="warning">
          This route has no valid point coordinates for the map.
        </NotificationBar>
      </CardBox>
      </SectionMain>
  </LayoutAuthenticated>
</template>

<script setup>

import { mdiTableBorder } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import NotificationBar from '@/components/NotificationBar.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import { VueDraggableNext } from 'vue-draggable-next'
import { GoogleMap, Marker, Polyline } from 'vue3-google-map'
import http from "@/lib/axios.js"
import {ref, onMounted, watch, reactive, computed} from "vue";
import FormControl from "@/components/FormControl.vue";
import FormField from "@/components/FormField.vue";
import BaseDivider from "@/components/BaseDivider.vue";
import BaseButtons from "@/components/BaseButtons.vue";
import BaseButton from "@/components/BaseButton.vue";
import { useTaskStatusStore } from "@/stores/taskStatus.js"


const selectedTask = ref(null)
const tasks = ref([])
const orders = ref([])
const points = ref([])
const error = ref(null)
const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || ''
const taskStatusStore = useTaskStatusStore()

const mapPoints = computed(() => points.value
  .map((point) => {
    const lat = Number(point.lat)
    const lng = Number(point.lng)

    return {
      ...point,
      position: { lat, lng },
    }
  })
  .filter((point) => Number.isFinite(point.position.lat) && Number.isFinite(point.position.lng)))

const mapCenter = computed(() => mapPoints.value[0]?.position || { lat: 0, lng: 0 })
const routePath = computed(() => mapPoints.value.map((point) => point.position))
const taskLineOptions = computed(() => ({
  path: routePath.value,
  geodesic: true,
  strokeColor: '#2563EB',
  strokeOpacity: 0.9,
  strokeWeight: 4,
}))

const taskStatus = computed(() => {
  if (!selectedTask.value) return null
  return taskStatusStore.tasks[selectedTask.value] ?? null
})

const isTaskEditable = computed(() => {
  if (!taskStatus.value) return true
  return taskStatus.value !== 'finished' && taskStatus.value !== 'canceled'
})

onMounted(() => {
  http.get('/api/active-tasks')
    .then((response) => {
      tasks.value = response.data.data.map(el => ({id: el.uuid, label: el.uuid + ': ' + el.courierName}))
      for (const task of response.data.data) {
        taskStatusStore.updateStatus({
          uuid: task.uuid,
          status: task.status,
        })
      }
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
let ordersData = []

const getOrders = () => {
  http.get('/api/orders-by-task/' + selectedTask.value)
    .then((response) => {
      orders.value = response.data.data
      ordersData = response.data.data
      orderStartPoints = orders.value.map(el => el.startPointId)
      orderEndPoints = orders.value.map(el => el.endPointId)
    })
}

const getRoute = () => {
  http.get('/api/route/' + selectedTask.value)
    .then((response) => {
      points.value = response.data.data
    })
}

const invalidFirstRoute = ref(false)
const invalidLastRoute = ref(false)
const invalidOrderSequence = ref(false)

const orderPoints = () => {
  invalidFirstRoute.value = false
  invalidLastRoute.value = false
  invalidOrderSequence.value = false

  if (! orderStartPoints.includes(points.value[0].pointId)) {
    invalidFirstRoute.value = true
  }
  if (! orderEndPoints.includes(points.value[points.value.length - 1].pointId)) {
    invalidLastRoute.value = true
  }

  // Validate that for each order, pickup comes before delivery
  for (const order of ordersData) {
    const pickupIdx = points.value.findIndex(p => p.pointId === order.startPointId)
    const deliveryIdx = points.value.findIndex(p => p.pointId === order.endPointId)
    if (pickupIdx !== -1 && deliveryIdx !== -1 && pickupIdx > deliveryIdx) {
      invalidOrderSequence.value = true
      break
    }
  }

  points.value.map((point, idx) => point.sequence = idx)
}

const form = reactive({
  taskUuid: null,
  points: [],
})

const submit = () => {
  http.post('/api/update-route',
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
