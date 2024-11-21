<script setup>
import {onMounted, reactive, ref} from 'vue'
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
import router from "@/router/index.js";
import Multiselect from "vue-multiselect";

const couriers = ref([])

const orders = ref([])

const form = reactive({
  courier: null,
  orders: [],
})

const submit = () => {
  console.log(form)
  axios.post('/api/task',
    {
      courierUuid: form.courier,
      orderUuids: form.orders.map(el => el.label),
    })
    .then((response) => {
      router.push({ path: 'tasks' })
    })
}

const formStatusWithHeader = ref(true)

const formStatusCurrent = ref(0)

const formStatusOptions = ['info', 'success', 'danger', 'warning']

onMounted(() => {
  axios.get('/api/task')
    .then((response) => {
      couriers.value = response.data.data.couriers.map(el => ({id: el.uuid, label: el.name}))
      orders.value = response.data.data.orders.map(el => ({id: el.id, label: el.uuid}))
      form.courier = couriers.value[0]
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
      <SectionTitleLineWithButton :icon="mdiBallotOutline" title="Create task" main>
      </SectionTitleLineWithButton>
      <CardBox form @submit.prevent="submit" :is-form="true">
        <FormField label="Couriers">
          <FormControl v-model="form.courier" :options="couriers"/>
        </FormField>

        <FormField label="Orders">
            <multiselect
              v-model="form.orders"
              :options="orders"
              :multiple="true"
              :close-on-select="false"
              :clear-on-select="false"
              :preserve-search="true"
              placeholder="Pick some"
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
