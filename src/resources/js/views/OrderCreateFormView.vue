<script setup>
import { onMounted, reactive, ref } from 'vue'
import { mdiBallotOutline, mdiAccount } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import axios from "@/lib/axios.js"
import router from "@/router/index.js"
import CardBoxModal from "@/components/CardBoxModal.vue"
import DatePicker from '@/components/DatePicker.vue'

const customers = ref([])
const points = ref([])
const unitTypes = ['Small', 'Medium', 'Large']
const isModalActive = ref(false)
const selectedDate = ref('');
const timeSlots = ref([]);

const dateOptions = {
  minDate: 'today',
  maxDate: (() => {
    const date = new Date();
    date.setDate(date.getDate() + 30); // 30 days from now
    return date;
  })(),
};

const handleDateChange = async (selectedDates, dateStr) => {
  form.slotId = null; // Reset selected slot when date changes
  if (!dateStr) {
    timeSlots.value = []
    return
  }
  //isLoading.value = true;
  try {
    const response = await axios.get(`/api/slots/available/${dateStr}`)
    timeSlots.value = response.data.data || []
    form.date = dateStr
  } catch (error) {
    console.error('Error fetching time slots:', error)
    timeSlots.value = []
  } finally {
    //isLoading.value = false;
  }
}


const selectTimeSlot = (slot) => {
  form.slotId = slot.id;
  // Emit an event or update a parent component if needed
  // emit('slot-selected', slot);
}

const form = reactive({
  customer: null,
  type: unitTypes[1],
  startAddress: null,
  endAddress: null,
  slotId: null,
  date: null,
})

const customerForm = reactive({
  name: null,
  lastName: null,
  email: null,
  phone: null,
})

const showModal = () => {
  isModalActive.value = true
}

const submit = () => {
  axios.post('/api/order',
    {
      customerEmail: form.customer,
      unitType: form.type,
      startAddressId: form.startAddress,
      endAddressId: form.endAddress,
      slotId: form.slotId,
      date: form.date,
    })
    .then((response) => {
      router.push({ path: 'orders' })
    })
}

const formStatusWithHeader = ref(true)

const formStatusCurrent = ref(0)

const formStatusOptions = ['info', 'success', 'danger', 'warning']

onMounted(async () => {
  const response = await axios.get('/api/customers')
  customers.value = response.data.data.map(el => el.email)
  await axios.get('/api/order')
    .then((response) => {
      points.value = response.data.data.points.map(el => ({id: el.id, label: el.address}))
      form.customer = customers.value[0]
      form.startAddress = points.value[0].id
      form.endAddress = points.value[1].id
    })
})

const formStatusSubmit = () => {
  formStatusCurrent.value = formStatusOptions[formStatusCurrent.value + 1]
    ? formStatusCurrent.value + 1
    : 0
}

const createCustomer = () => {
  const endpoint = '/api/create-customer'
  const data = {
    first_name: customerForm.name,
    last_name: customerForm.lastName,
    email: customerForm.email,
    phone: customerForm.phone,
  }

  axios.post(endpoint, data)
    .then((response) => {
      customers.value.unshift(response.data.data.email)
      form.customer = customers.value[0]
    })
    .finally(() => {
      customerForm.name = null
      customerForm.lastName = null
      customerForm.email = null
      customerForm.phone = null
    })
}

</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBallotOutline" title="Create order" main>
      </SectionTitleLineWithButton>
      <CardBoxModal
        v-model="isModalActive"
        title="Create a customer"
        button-label="Create"
        @update:modelValue="createCustomer"
        has-cancel
      >
        <FormField label="First name">
          <FormControl v-model="customerForm.name" :icon="mdiAccount" />
        </FormField>
        <FormField label="Last name">
          <FormControl v-model="customerForm.lastName" :icon="mdiAccount" />
        </FormField>
        <FormField label="Email">
          <FormControl v-model="customerForm.email" :icon="mdiAccount" />
        </FormField>
        <FormField label="Phone">
          <FormControl v-model="customerForm.phone" :icon="mdiAccount" />
        </FormField>
      </CardBoxModal>

      <CardBox form @submit.prevent="submit" :is-form="true">
        <FormField label="Customer">
          <FormControl v-model="form.customer" :options="customers" />
        </FormField>
        <BaseButtons>
          <BaseButton type="button" color="info" label="Create customer" @click="showModal()"/>
        </BaseButtons>

        <FormField label="Unit type">
          <FormControl v-model="form.type" :options="unitTypes" />
        </FormField>

        <FormField label="Pick up">
          <FormControl v-model="form.startAddress" :options="points" />
        </FormField>

        <FormField label="Destination">
          <FormControl v-model="form.endAddress" :options="points" />
        </FormField>

        <DatePicker
          v-model="selectedDate"
          label="Pickup Date"
          placeholder="Choose a date"
          :options="dateOptions"
          @on-change="handleDateChange"
        />
        <p v-if="selectedDate" class="mt-2 text-gray-600">
          Selected: {{ selectedDate }}
        </p>

        <FormField v-if="timeSlots.length > 0" label="Available Time Slots" help="Select a time slot for delivery">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
            <div
              v-for="slot in timeSlots"
              :key="slot.id"
              @click="selectTimeSlot(slot)"
              class="border rounded-lg p-4 cursor-pointer transition-colors"
              :class="{
          'border-blue-500 bg-blue-50 dark:bg-blue-900/20': form.slotId === slot.id,
          'border-gray-200 hover:border-blue-300 dark:border-gray-600 dark:hover:border-blue-600': form.slotId !== slot.id
        }"
            >
              <div class="font-medium">{{ slot.from }} - {{ slot.to }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                Available: {{ slot.available }} of {{ slot.capacity }}
              </div>
            </div>
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
