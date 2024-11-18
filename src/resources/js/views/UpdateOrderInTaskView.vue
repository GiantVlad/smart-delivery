<script setup>
import {onMounted, reactive, ref, watch} from 'vue'
import { mdiBallotOutline } from '@mdi/js'
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

const selectedTask = ref({id: null, label: ''})
const tasks = ref([])
const orders = ref([])

const form = reactive({
  status: null,
  orderUuid: null,
})

const submit = () => {
  console.log(form)
  axios.post('/api/update-order-status-in-task',
    {
      status: form.status,
      orderUuids: form.orderUuid,
    })
    .then(response => {
      console.log('Updated')
    })
}

const formStatusWithHeader = ref(true)

const formStatusCurrent = ref(0)

const formStatusOptions = ['info', 'success', 'danger', 'warning']

onMounted(() => {
  axios.get('/api/tasks')
    .then((response) => {
      tasks.value = response.data.data.map(el => ({id: el.uuid, label: el.uuid + ': ' + el.courierName}))
    })
})

watch(selectedTask, async (newTask, oldTask) => {
  console.log(newTask)
  if (newTask !== null && (newTask.id !== oldTask?.id)) {
    await getOrders()
  }
})

const getOrders = () => {
  axios.get('/api/orders-by-task/' + selectedTask.id)
    .then((response) => {
      orders.value = response.data.data
    })
}

const formStatusSubmit = () => {
  formStatusCurrent.value = formStatusOptions[formStatusCurrent.value + 1]
    ? formStatusCurrent.value + 1
    : 0
}

</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBallotOutline" title="Update order in task" main>
      </SectionTitleLineWithButton>
      <CardBox form @submit.prevent="submit" :is-form="true">
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
        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Submit" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
</LayoutAuthenticated>
</template>

<style>
  @import "vue-multiselect/dist/vue-multiselect.css";
</style>
