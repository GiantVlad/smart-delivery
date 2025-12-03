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
        <div class="max-h-96 overflow-y-auto">
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
      </div>
      </CardBox>

      <!-- Holidays Section -->
      <SectionTitleLineWithButton :icon="mdiCalendar" title="Holidays" class="mt-12">
        <BaseButton
          color="info"
          label="Add Holiday"
          @click="showHolidayModal = true"
        />
      </SectionTitleLineWithButton>

      <CardBox class="mb-6" has-table>
        <div v-if="loadingHolidays" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        </div>

        <div v-else class="overflow-x-auto max-h-96">
          <table class="min-w-full bg-white dark:bg-gray-800">
            <colgroup>
              <col class="w-1/3">
              <col class="w-1/3">
              <col class="w-1/3">
            </colgroup>
            <thead class="text-xs font-semibold uppercase dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50">
              <tr>
                <th class="p-2 whitespace-nowrap">
                  <div class="font-semibold text-left">Date</div>
                </th>
                <th class="p-2 whitespace-nowrap">
                  <div class="font-semibold text-left">Reason</div>
                </th>
                <th class="p-2 whitespace-nowrap">
                  <div class="font-semibold text-right">Actions</div>
                </th>
              </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60">
              <tr v-for="holiday in holidays" :key="holiday.id" class="border-t dark:border-gray-700">
                <td class="p-3 whitespace-nowrap">
                  <div class="text-left">{{ formatDate(holiday.date) }}</div>
                </td>
                <td class="p-3 whitespace-nowrap">
                  <div class="text-left">{{ getReasonText(holiday.reason_code) }}</div>
                </td>
                <td class="p-3 whitespace-nowrap text-right">
                  <button
                    @click="deleteHoliday(holiday.id)"
                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                    :disabled="deletingHoliday === holiday.id"
                  >
                    <span v-if="deletingHoliday === holiday.id">Deleting...</span>
                    <span v-else>Delete</span>
                  </button>
                </td>
              </tr>
              <tr v-if="holidays.length === 0" class="border-t dark:border-gray-700">
                <td colspan="3" class="p-4 text-center text-gray-500 dark:text-gray-400">
                  No holidays found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <!-- Add Holiday Modal -->
      <CardBox v-if="showHolidayModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 mx-4">
          <h2 class="text-xl font-semibold mb-4 dark:text-white">Add Holiday</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
              <input
                type="date"
                v-model="holidayForm.date_from"
                class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                required
              />
              <p v-if="errors.date_from" class="text-red-500 text-xs mt-1">{{ errors.date_from[0] }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
              <input
                type="date"
                v-model="holidayForm.date_to"
                class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                :min="holidayForm.date_from"
                required
              />
              <p v-if="errors.date_to" class="text-red-500 text-xs mt-1">{{ errors.date_to[0] }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason</label>
              <select
                v-model="holidayForm.reason_code"
                class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                required
              >
                <option v-for="reason in holidayReasons" :key="reason.value" :value="reason.value">
                  {{ reason.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <BaseButton
              color="danger"
              outline
              label="Cancel"
              @click="showHolidayModal = false"
              :disabled="isAddingHoliday"
            />
            <BaseButton
              color="success"
              :label="isAddingHoliday ? 'Saving...' : 'Save'"
              @click="addHoliday"
              :disabled="isAddingHoliday"
            />
          </div>
        </div>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<script setup>
import { mdiTableBorder, mdiCalendar } from '@mdi/js'
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
const loadingHolidays = ref(false)
const isAddingHoliday = ref(false)
const deletingHoliday = ref(null)
const showHolidayModal = ref(false)
const holidays = ref([])

const holidayForm = reactive({
  courier_id: courierId,
  date_from: '',
  date_to: '',
  reason_code: 0,
});

const holidayReasons = [
  { value: 0, label: 'Vacation' },
  { value: 1, label: 'Sick Leave' },
  { value: 2, label: 'Day Off' },
  { value: 3, label: 'Public Holiday' },
];

const errors = ref({})

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

const fetchHolidays = async () => {
  loadingHolidays.value = true
  try {
    const response = await axios.get(`/api/courier-holidays/${courierId}`)
    holidays.value = response.data.data
  } catch (error) {
    console.error('Error fetching holidays:', error)
  } finally {
    loadingHolidays.value = false
  }
}

const addHoliday = async () => {
  isAddingHoliday.value = true
  errors.value = {}

  try {
    const response = await axios.post('/api/courier-holidays', holidayForm)
    holidays.value = response.data.data
    showHolidayModal.value = false

    // Reset form
    holidayForm.date_from = ''
    holidayForm.date_to = ''
    holidayForm.reason_code = 0
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      console.error('Error adding holiday:', error)
    }
  } finally {
    isAddingHoliday.value = false
  }
}

const deleteHoliday = async (id) => {
  if (!confirm('Are you sure you want to delete this holiday?')) return

  deletingHoliday.value = id

  try {
    const holiday = holidays.value.find(h => h.id === id)
    if (!holiday) return

    // Format date as YYYY-MM-DD
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toISOString().split('T')[0];
    };

    await axios.post('/api/courier-holidays-delete', {
      courier_id: courierId,
      date_from: formatDate(holiday.date),
      date_to: formatDate(holiday.date)
    })

    holidays.value = holidays.value.filter(h => h.id !== id)
  } catch (error) {
    console.error('Error deleting holiday:', error)
  } finally {
    deletingHoliday.value = null
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getReasonText = (code) => {
  const reason = holidayReasons.find(r => r.value === code);
  return reason ? reason.label : 'Unknown';
};

onMounted(() => {
  // Load working hours
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

  // Load holidays
  fetchHolidays()
})

</script>
