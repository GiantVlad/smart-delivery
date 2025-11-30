<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTableBorder" title="Courier's working hours" main>
      </SectionTitleLineWithButton>
      <CardBox class="mb-6" has-table>
        <!-- Table -->
        <table class="table-auto w-full">
          <!-- Table header -->
          <thead class="text-xs font-semibold uppercase dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50">
          <tr>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Day of the week</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">From</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">To</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Action</div>
            </th>
          </tr>
          </thead>
          <!-- Table body -->
          <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60">
          <tr v-for="weekday in weekdays" :key="weekday.id">
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">{{weekday.day}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">
                <input 
                  type="time" 
                  v-model="weekday.from" 
                  class="w-24 p-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                />
              </div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left">
                <input 
                  type="time" 
                  v-model="weekday.to" 
                  class="w-24 p-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                />
              </div>
            </td>
            <div class="text-left font-small">
              <BaseButton 
                type="button" 
                color="success" 
                label="Save" 
                small 
                :disabled="!hasChanges(weekday)"
                :class="{ 'opacity-50 cursor-not-allowed': !hasChanges(weekday) }"
                @click="save(weekday)"
              />
            </div>
          </tr>
          </tbody>
        </table>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<script setup>
import { mdiTableBorder } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import axios from "@/lib/axios.js"
import { onMounted, reactive, ref, watch } from "vue"
import { useRoute } from 'vue-router'
import BaseButton from "@/components/BaseButton.vue"

const route = useRoute()
const courierId = route.params.courierId

const weekdays = reactive([])
const initialValues = ref({})

const hasChanges = (weekday) => {
  const initial = initialValues.value[weekday.id]
  if (!initial) return false
  return weekday.from !== initial.from || weekday.to !== initial.to
}

const form = reactive({
  courier_id: courierId,
  day: null,
  from: null,
  to: null,
})

const save = (weekday) => {
  form.courier_id = courierId
  form.day = weekday.day
  form.from = weekday.from
  form.to = weekday.to

  axios.post(`/api/working-hours/${courierId}`, form)
    .then((response) => {
        weekdays.map(el => {
          if (el.courier_id === response.data.data.courier_id) {
            el.day = response.data.data.day
            el.from = response.data.data.from
            el.to = response.data.data.to
          }

          return el
        })
    })
    .finally(() => {
      form.courier_id = courierId
      form.day = null
      form.from = null
      form.to = null
    })
}

const formatTimeForInput = (timeString) => {
  if (!timeString) return ''
  // If time is already in HH:MM format, return as is
  if (/^\d{2}:\d{2}$/.test(timeString)) {
    return timeString
  }
  // Parse time string and format to HH:MM
  const [hours, minutes] = timeString.split(':')
  return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`
}

onMounted(() => {
  axios.get(`/api/working-hours/${courierId}`)
    .then((response) => {
      const data = response.data.data.map(item => ({
        ...item,
        from: formatTimeForInput(item.from),
        to: formatTimeForInput(item.to)
      }))
      weekdays.push(...data)
      // Store initial values for change detection
      initialValues.value = data.reduce((acc, item) => {
        acc[item.id] = { from: item.from, to: item.to }
        return acc
      }, {})
    })
    .catch(error => {
      console.error('Error fetching working hours:', error)
    })
})

</script>
