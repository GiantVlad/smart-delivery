<script setup>
import { computed, onMounted, ref } from 'vue'
import TableCheckboxCell from '@/components/TableCheckboxCell.vue'
import BaseLevel from '@/components/BaseLevel.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseButton from '@/components/BaseButton.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import http from '@/lib/axios.js'

defineProps({
  checkable: Boolean
})

const items = ref([])
const loading = ref(false)
const loadError = ref('')

const perPage = ref(5)

const currentPage = ref(0)

const checkedRows = ref([])

const itemsPaginated = computed(() =>
  items.value.slice(perPage.value * currentPage.value, perPage.value * (currentPage.value + 1))
)

const numPages = computed(() => Math.ceil(items.value.length / perPage.value))

const currentPageHuman = computed(() => currentPage.value + 1)

const pagesList = computed(() => {
  const pagesList = []

  for (let i = 0; i < numPages.value; i++) {
    pagesList.push(i)
  }

  return pagesList
})

const remove = (arr, cb) => {
  const newArr = []

  arr.forEach((item) => {
    if (!cb(item)) {
      newArr.push(item)
    }
  })

  return newArr
}

const checked = (isChecked, customer) => {
  if (isChecked) {
    checkedRows.value.push(customer)
  } else {
    checkedRows.value = remove(checkedRows.value, (row) => row.id === customer.id)
  }
}

const fetchCustomers = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await http.get('/api/customers/100')
    items.value = response.data?.data || []
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Failed to load customers.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCustomers()
})
</script>

<template>
  <table v-if="!loading && !loadError && items.length > 0">
    <thead>
      <tr>
        <th v-if="checkable" />
        <th />
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Phone</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="customer in itemsPaginated" :key="customer.id">
        <TableCheckboxCell v-if="checkable" @checked="checked($event, customer)" />
        <td class="border-b-0 lg:w-6 before:hidden">
          <UserAvatar :username="customer.name" class="w-24 h-24 mx-auto lg:w-6 lg:h-6" />
        </td>
        <td data-label="Name">
          {{ customer.name }}
        </td>
        <td data-label="Last Name">
          {{ customer.last_name || '-' }}
        </td>
        <td data-label="Email">
          {{ customer.email }}
        </td>
        <td data-label="Phone">
          {{ customer.phone || '-' }}
        </td>
      </tr>
    </tbody>
  </table>
  <div v-else-if="loading" class="p-4 text-sm text-gray-500">Loading customers...</div>
  <div v-else-if="loadError" class="p-4 text-sm text-red-600">{{ loadError }}</div>
  <div v-else class="p-4 text-sm text-gray-500">No customers found.</div>

  <div v-if="numPages > 0" class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800">
    <BaseLevel>
      <BaseButtons>
        <BaseButton
          v-for="page in pagesList"
          :key="page"
          :active="page === currentPage"
          :label="page + 1"
          :color="page === currentPage ? 'lightDark' : 'whiteDark'"
          small
          @click="currentPage = page"
        />
      </BaseButtons>
      <small>Page {{ currentPageHuman }} of {{ numPages }}</small>
    </BaseLevel>
  </div>
</template>
