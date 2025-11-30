<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTableBorder" title="Courier's working hours" main>
        <BaseButton
          color="info"
          label="Add Working Hours"
          @click="showAddForm = !showAddForm"
        />
      </SectionTitleLineWithButton>

      <!-- Add New Working Hours Form -->
      <CardBox v-if="showAddForm" class="mb-6 p-6">
        <h3 class="text-lg font-medium mb-4">Add New Working Hours</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Day</label>
            <select v-model="newWorkingHours.day" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
              <option v-for="day in availableDays" :key="day" :value="day">
                {{ day.charAt(0).toUpperCase() + day.slice(1) }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
            <input
              type="time"
              v-model="newWorkingHours.from"
              class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
            <input
              type="time"
              v-model="newWorkingHours.to"
              class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600"
              :min="newWorkingHours.from"
              required
            />
          </div>
          <div class="flex space-x-2">
            <BaseButton
              color="success"
              label="Save"
              :disabled="isSaving"
              :class="{ 'opacity-50 cursor-not-allowed': isSaving }"
              @click="createWorkingHours"
            />
            <BaseButton
              color="danger"
              outline
              label="Cancel"
              @click="showAddForm = false"
            />
          </div>
        </div>
      </CardBox>
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
                :label="saving[weekday.id] ? 'Saving...' : 'Save'"
                small
                :disabled="!hasChanges(weekday) || saving[weekday.id]"
                :class="{ 'opacity-50 cursor-not-allowed': !hasChanges(weekday) || saving[weekday.id] }"
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
import { computed, onMounted, reactive, ref } from "vue"
import { useRoute } from 'vue-router'
import BaseButton from "@/components/BaseButton.vue"

const route = useRoute()
const courierId = route.params.courierId

const weekdays = reactive([])
const initialValues = ref({})
const saving = reactive({})
const showAddForm = ref(false)
const isSaving = ref(false)

const newWorkingHours = reactive({
  day: 'monday',
  from: '09:00',
  to: '18:00'
})

// Get available days that aren't already in the weekdays list
const availableDays = computed(() => {
  const usedDays = weekdays.map(w => w.day.toLowerCase())
  return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
    .filter(day => !usedDays.includes(day))
})

const hasChanges = (weekday) => {
  const initial = initialValues.value[weekday.id]
  if (!initial) return false
  return weekday.from !== initial.from || weekday.to !== initial.to
}

const form = reactive({
  id: null,
  courier_id: courierId,
  day: null,
  from: null,
  to: null,
})

const save = async (weekday) => {
  form.id = weekday.id
  form.from = weekday.from
  form.to = weekday.to

  saving[weekday.id] = true

  try {
    const response = await axios.post(`/api/working-hours/${form.id}`, form)

    // Update the weekday in the weekdays array
    const updatedWeekday = response.data.data
    const index = weekdays.findIndex(w => w.id === updatedWeekday.id)
    if (index !== -1) {
      weekdays[index].from = formatTimeForInput(updatedWeekday.from)
      weekdays[index].to = formatTimeForInput(updatedWeekday.to)

      // Update initial values to match the saved state
      initialValues.value[weekday.id] = {
        from: weekdays[index].from,
        to: weekdays[index].to
      }
    }
  } catch (error) {
    console.error('Error saving working hours:', error)
    const errorMessage = error.response?.data?.message || 'Failed to update working hours'
  } finally {
    form.id = null
    form.day = null
    form.from = null
    form.to = null
    saving[weekday.id] = false
  }
}

const createWorkingHours = async () => {
  if (!newWorkingHours.day || !newWorkingHours.from || !newWorkingHours.to) {
    console.error('Please fill in all fields')
    return
  }

  if (newWorkingHours.from >= newWorkingHours.to) {
    console.error('End time must be after start time')
    return
  }

  isSaving.value = true

  try {
    const response = await axios.post('/api/working-hours', {
      courier_id: courierId,
      ...newWorkingHours
    })

    const newEntry = response.data.data
    newEntry.from = formatTimeForInput(newEntry.from)
    newEntry.to = formatTimeForInput(newEntry.to)

    weekdays.push(newEntry)
    initialValues.value[newEntry.id] = {
      from: newEntry.from,
      to: newEntry.to
    }

    // Reset form
    newWorkingHours.day = availableDays.value[0] || 'monday'
    newWorkingHours.from = '09:00'
    newWorkingHours.to = '18:00'

    showAddForm.value = false

  } catch (error) {
    console.error('Error creating working hours:', error)
    const errorMessage = error.response?.data?.message || 'Failed to create working hours'
    console.error(errorMessage)
  } finally {
    isSaving.value = false
  }
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
      console.error('Failed to load working hours')
    })
})

</script>
