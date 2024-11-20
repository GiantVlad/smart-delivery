<script setup>
import {computed, onMounted, reactive, ref, watch} from 'vue'
import {mdiBallotOutline, mdiClose} from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import axios from "axios";
import {OrderStatuses} from "@/constants/Statuses.js";
import NotificationBar from "@/components/NotificationBar.vue";
import Multiselect from "vue-multiselect";

const selectedTask = ref(null)
const tasks = ref([])
const orders = ref([])
const selectedStatus = ref({})
const error = ref('')

const form = reactive({
  status: null,
  orderUuid: null,
})

const showActionButton = ref({})
const onUpdateStatus = (order) => {
  showActionButton.value[order.uuid] = !showActionButton.value[order.uuid]
  if (!showActionButton.value[order.uuid]) {
    selectedStatus.value[order.uuid] = order.status
  } else {
    form.orderUuid = null
  }
}

onMounted(() => {
  axios.get('/api/tasks')
    .then((response) => {
      tasks.value = response.data.data.map(el => ({id: el.uuid, label: el.uuid + ': ' + el.courierName}))
    })
})

watch(selectedTask, async (newTask, oldTask) => {
  console.log(newTask)
  if (newTask !== null && (newTask !== oldTask)) {
    await getOrders()
  }
})

const getOrders = () => {
  axios.get('/api/orders-by-task/' + selectedTask.value)
    .then((response) => {
      for (const order of response.data.data) {
        showActionButton.value[order.uuid] = true
        selectedStatus.value[order.uuid] = order.status
      }

      orders.value = response.data.data
    })
}

const unassignOrder = (order) => {
  axios.post('/api/unassign-order', {
    orderUuid: order.uuid,
  })
    .then(response => {
      orders.value = orders.value.filter(el => el.uuid !== order.uuid)
    }).catch(e => {
      error.value = e.response.data.message
  })
}

const submit = (orderUuid) => {
  axios.post('/api/update-order-status-in-task',
    {
      status: selectedStatus.value[orderUuid],
      orderUuid: orderUuid,
    })
    .then(response => {
      showActionButton.value[form.orderUuid] = true
      orders.value.map(el => {
        if (el.uuid === form.orderUuid) {
          el.status = form.status
        }
      })
      showActionButton.value[orderUuid] = true
      console.log('Updated')
    })
}

const orderSelector = ref(false)
const ordersToAdd = ref([])
const selectedOrdersToAdd = ref([])
const showOrderSelector = () => {
  axios.get('/api/task')
    .then((response) => {
      ordersToAdd.value = response.data.data.orders.map(el => ({id: el.id, label: el.uuid}))
      orderSelector.value = true
    })
}

const hideOrderSelector = () => {
  ordersToAdd.value = []
  selectedOrdersToAdd.value = []
  orderSelector.value = false
}

const dismiss = () => error.value = ''

const addOrdersToTask = () => {
  console.log(selectedOrdersToAdd.value)
  axios.post('/api/add-orders-to-task', {
    taskUuid: selectedTask.value,
    ordersUuids: selectedOrdersToAdd.value.map(el => el.label)
  })
    .then((response) => {
      ordersToAdd.value = response.data.data.orders.map(el => ({id: el.id, label: el.uuid}))
      orderSelector.value = true
    })
}


</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBallotOutline" title="Update orders in task" main>
      </SectionTitleLineWithButton>
      <CardBox form :is-form="true">
        <FormField label="Task">
          <FormControl v-model="selectedTask" :options="tasks"/>
        </FormField>

        <BaseDivider />

        <NotificationBar color="danger" v-if="error && selectedTask">
          <template #right>
            <BaseButton :icon="mdiClose" small rounded-full color="white" @click="dismiss" />
          </template>
          {{error}}
        </NotificationBar>
        <table class="table-auto w-full" v-if="selectedTask !== null">
          <!-- Table header -->
          <thead class="text-xs font-semibold uppercase dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50">
          <tr>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Order</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Type</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-center">Status</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-center">Action</div>
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
              <div class="items-center">
                <div class="font-medium text-gray-800 dark:text-gray-100">{{order.uuid}}</div>
                <div class="text-left">{{order.startPointAddress}}</div>
                <div class="text-left">{{order.endPointAddress}}</div>
              </div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{order.unitType}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left font-medium text-green-500">{{order.status}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left font-medium text-green-500">
                <BaseButton v-show="showActionButton[order.uuid]" type="button" color="success" label="Change status" small @click="onUpdateStatus(order)"/>
                <div v-show="!showActionButton[order.uuid]">
                  <FormControl v-model="selectedStatus[order.uuid]" :options="Object.values(OrderStatuses)"/>
                  <BaseButton class="pt-1" type="button" color="info" label="Submit" small @click="submit(order.uuid)"/>
                </div>
              </div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{order.startPointAddress}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{order.endPointAddress}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left font-medium text-green-500">
                <BaseButton type="button" color="success" label="Unassign order" small @click="unassignOrder(order)"/>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
        <template #footer>
          <div v-if="orderSelector">
          <FormField label="Orders">
            <multiselect
              v-model="selectedOrdersToAdd.value"
              :options="ordersToAdd"
              :multiple="true"
              :close-on-select="false"
              :clear-on-select="false"
              :preserve-search="true"
              placeholder="Pick some orders"
              label="label"
              track-by="label"
              :preselect-first="true"
            >
              <template #selection="{ values, search, isOpen }">
                <span class="multiselect__single" v-if="values.length" v-show="!isOpen">
                  {{ values.length }} selected
                </span>
              </template>
            </multiselect>
          </FormField>
          <BaseButtons>
            <BaseButton type="button" color="info" label="Add" small @click="addOrdersToTask"/>
            <BaseButton type="button" color="danger" label="Cancel" small @click="hideOrderSelector"/>
          </BaseButtons>
          </div>
          <BaseButtons v-else>
            <BaseButton type="button" color="info" label="Add orders" @click="showOrderSelector"/>
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
</LayoutAuthenticated>
</template>

<style>
  @import "vue-multiselect/dist/vue-multiselect.css";
</style>
