<script setup>
import {onMounted, reactive, ref} from 'vue'
import { mdiBallotOutline, mdiAccount, mdiMail } from '@mdi/js'
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
import router from "@/router/index.js";

const customers = ref([])

const points = ref([])

const unitTypes = ['Small', 'Medium', 'Large']

const form = reactive({
  customer: null,
  type: unitTypes[1],
  startAddress: null,
  endAddress: null,
})

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

onMounted(() => {
  axios.get('/api/order')
    .then((response) => {
      customers.value = response.data.data.customerEmails
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
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBallotOutline" title="Create order" main>
      </SectionTitleLineWithButton>
      <CardBox form @submit.prevent="submit" :is-form="true">
        <FormField label="Customer">
          <FormControl v-model="form.customer" :options="customers" />
        </FormField>

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
