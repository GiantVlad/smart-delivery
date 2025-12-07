<script setup>
import {onMounted, reactive, ref, watch, computed} from 'vue'
import { mdiBallotOutline, mdiCalendar } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import axios from "@/lib/axios.js";
import router from "@/router/index.js";
import Multiselect from "vue-multiselect";
import {useOrderStatusStore} from "@/stores/orderStatus.js";

const couriers = ref([])

const orders = ref([])

const form = reactive({
  date: new Date().toISOString().split('T')[0], // Default to today
  courier: null,
  orders: [],
  selectedOrders: new Set(), // Track selected order IDs
})

const dateError = ref('')

const validateForm = () => {
  dateError.value = !form.date ? 'Date is required' : ''
  return !dateError.value
}

const submit = () => {
  if (!validateForm()) return

  axios.post('/api/task',
    {
      date: form.date,
      courierUuid: form.courier?.id,
      orderUuids: form.orders.map(el => el.label),
    })
    .then((response) => {
      router.push({ path: 'tasks' })
    })
    .catch(error => {
      console.error('Error creating task:', error)
    })
}

// Watch for date changes to reload data
watch(() => form.date, (newDate) => {
  if (newDate) {
    getTask()
  }
})

const orderStatusStore = useOrderStatusStore()

watch(orderStatusStore.orders, (orders) => {
  getTask()
})

const getTask = () => {
  axios.get(`/api/task-form/${form.date}`)
    .then((response) => {
      couriers.value = response.data.data.couriers.map(el => ({id: el.uuid, label: el.name}))
      // Map the orders to include all necessary fields for the table
      orders.value = response.data.data.orders.map(el => ({
        id: el.id,
        label: el.uuid,
        time: `${el.from} - ${el.to}`,
        pickup: el.start_point.address || '-',
        delivery: el.end_point.address || '-',
        unitType: el.unit_type || '-'
      }))
      form.courier = couriers.value[0] || null
      form.selectedOrders.clear()
    })
    .catch(error => {
      console.error('Error fetching tasks:', error)
    })
}

const isOrderSelected = (order) => {
  return form.selectedOrders.has(order.id)
}

const selectOrder = (order) => {
  if (form.selectedOrders.has(order.id)) {
    form.selectedOrders.delete(order.id)
  } else {
    form.selectedOrders.add(order.id)
  }
  // Update form.orders with the selected order objects
  form.orders = Array.from(form.selectedOrders).map(id =>
    orders.value.find(order => order.id === id)
  )
}

onMounted(() => {
  getTask()
})

const formStatusSubmit = () => {
  formStatusCurrent.value = formStatusOptions[formStatusCurrent.value + 1]
    ? formStatusCurrent.value + 1
    : 0
}
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBallotOutline" title="Create task" main>
      </SectionTitleLineWithButton>
      <CardBox form @submit.prevent="submit" :is-form="true">
        <FormField label="Date" :error="dateError" required>
          <FormControl
            v-model="form.date"
            type="date"
            :icon="mdiCalendar"
            required
            @update:modelValue="validateForm"
          />
        </FormField>

        <BaseDivider />

        <FormField label="Couriers">
          <FormControl v-model="form.courier" :options="couriers"/>
        </FormField>

        <FormField label="Orders">
          <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
              <thead>
                <tr class="text-left font-bold">
                  <th class="px-6 pt-6 pb-4">ID</th>
                  <th class="px-6 pt-6 pb-4">Time</th>
                  <th class="px-6 pt-6 pb-4">Pickup</th>
                  <th class="px-6 pt-6 pb-4">Delivery</th>
                  <th class="px-6 pt-6 pb-4">Unit Type</th>
                  <th class="px-6 pt-6 pb-4">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
                  <td class="border-t px-6 py-4">
                    {{ order.id }}
                  </td>
                  <td class="border-t px-6 py-4">
                    {{ order.time }}
                  </td>
                  <td class="border-t px-6 py-4">
                    {{ order.pickup }}
                  </td>
                  <td class="border-t px-6 py-4">
                    {{ order.delivery }}
                  </td>
                  <td class="border-t px-6 py-4">
                    {{ order.unitType }}
                  </td>
                  <td class="border-t px-6 py-4">
                    <BaseButton
                      v-if="!isOrderSelected(order)"
                      type="button"
                      color="info"
                      label="Select"
                      small
                      @click="selectOrder(order)"
                    />
                    <span v-else class="text-green-500 font-medium">
                      Selected
                    </span>
                  </td>
                </tr>
                <tr v-if="!orders.length">
                  <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                    No orders available for the selected date
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </FormField>

        <BaseDivider />

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
