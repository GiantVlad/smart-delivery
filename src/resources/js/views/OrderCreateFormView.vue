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
import axios from "axios"
import router from "@/router/index.js"
import CardBoxModal from "@/components/CardBoxModal.vue"

const customers = ref([])
const points = ref([])
const unitTypes = ['Small', 'Medium', 'Large']
const isModalActive = ref(false)

const form = reactive({
  customer: null,
  type: unitTypes[1],
  startAddress: null,
  endAddress: null,
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
      endAddressId: form.endAddress
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
  customers.value = response.data.data.points.map(el => el.email)
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
  }

  axios.post(endpoint, data)
    .then((response) => {
      console.log(response.data.data)
    })
    .finally(() => {
      customerForm.name = null
      customerForm.lastName = null
      customerForm.email = null
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
