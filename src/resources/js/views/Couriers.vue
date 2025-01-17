<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTableBorder" title="Couriers" main>
      </SectionTitleLineWithButton>
      <CardBoxModal
        v-model="isModalActive"
        :title="form.uuid ? 'Edit courier' : 'Create courier'"
        :button-label="form.uuid ? 'Update' : 'Create'"
        @update:modelValue="updateCourier"
        has-cancel
      >
        <FormField label="Name">
          <FormControl v-if="form.uuid" v-model="form.uuid" :icon="mdiAccount" is-disabled/>
          <FormControl v-model="form.name" :icon="mdiAccount" />
        </FormField>
        <FormField label="Phone">
          <FormControl v-model="form.phone" :icon="mdiAccount" />
        </FormField>
        <FormField label="Status" v-if="form.uuid" >
          <FormControl v-model="form.status" :options="statuses" />
        </FormField>
      </CardBoxModal>
      <CardBox class="mb-6" has-table>
        <!-- Table -->
        <table class="table-auto w-full">
          <!-- Table header -->
          <thead class="text-xs font-semibold uppercase dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50">
          <tr>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">#</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">UUID</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Name</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Status</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Action</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Date</div>
            </th>
          </tr>
          </thead>
          <!-- Table body -->
          <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60">
          <tr v-for="courier in couriers" :key="courier.id">
            <td class="p-2 whitespace-nowrap">
              <div class="flex items-center">
                <div class="font-medium text-gray-800 dark:text-gray-100">{{ courier.id }}</div>
              </div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="flex items-center">
                <div class="font-medium text-gray-800 dark:text-gray-100">{{courier.uuid}}</div>
              </div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{courier.name}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left font-medium text-green-500">
                {{ courierStatuses.find(el => el.uuid === courier.uuid)?.status }}
              </div>
            </td>
            <div class="text-left font-small">
              <BaseButton type="button" color="success" label="Edit courier" small @click="showModal(courier)"/>
            </div>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{courier.updated_at}}</div>
            </td>
          </tr>
          </tbody>
        </table>
        <template #footer>
          <BaseButtons>
            <BaseButton type="button" color="info" label="Create" @click="showModal(null)"/>
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<script setup>

import { mdiAccount, mdiTableBorder } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import axios from "@/lib/axios.js"
import {ref, onMounted, reactive, computed} from "vue"
import CardBoxModal from "@/components/CardBoxModal.vue"
import BaseButton from "@/components/BaseButton.vue"
import FormField from "@/components/FormField.vue"
import FormControl from "@/components/FormControl.vue"
import { CourierStatuses } from "@/constants/Statuses.js"
import BaseButtons from "@/components/BaseButtons.vue"
import { useCourierStatusStore } from "@/stores/courierStatus.js"

const couriers = reactive([])
const statuses = Object.values(CourierStatuses)
const isModalActive = ref(false)
const form = reactive({
  name: null,
  status: null,
  uuid: null,
  phone: null,
})

const courierStatusStore = useCourierStatusStore()

const courierStatuses = computed(() => {
  const arr = []

  for (const [key, value] of Object.entries(courierStatusStore.couriers)) {
    arr.push({uuid: key, status: value});
  }

  return arr
})

const showModal = (courier) => {
  isModalActive.value = true
  form.name = courier?.name
  form.uuid = courier?.uuid
  form.status = courier?.status
  form.phone = courier?.phone
}

const updateCourier = () => {
  const endpoint = form.uuid ? '/api/update-courier' : '/api/create-courier'
  const data = {
    name: form.name,
    phone: form.phone,
  }

  if (form.uuid) {
    data.uuid = form.uuid
    data.status = form.status
  }

  axios.post(endpoint, data)
    .then((response) => {
      if (form.uuid) {
        couriers.map(el => {
          if (el.uuid === response.data.data.uuid) {
            el.name = response.data.data.name
            el.name = response.data.data.phone
            el.status = response.data.data.status
          }

          return el
        })
      } else {
        axios.get('/api/couriers')
          .then((response) => {
            couriers.push(...response.data.data)
          })
      }
    })
    .finally(() => {
      form.name = null
      form.uuid = null
      form.status = null
      form.phone = null
    })
}

onMounted(() => {
  axios.get('/api/couriers')
    .then((response) => {
      couriers.push(...response.data.data)
      for (const courier of response.data.data) {
        courierStatusStore.updateStatus({
          uuid: courier.uuid,
          status: courier.status,
        })
      }
    })
})

</script>
