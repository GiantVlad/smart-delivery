<template>
  <div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <Sidebar :sidebarOpen="sidebarOpen" @close-sidebar="sidebarOpen = false" />

    <!-- Content area -->
    <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">

      <!-- Site header -->
      <Header :sidebarOpen="sidebarOpen" @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="grow">
          <div class="col-span-full xl:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
              <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                  <h2 class="font-semibold text-gray-800 dark:text-gray-100">Tasks</h2>
              </header>
              <div class="p-3">

                  <!-- Table -->
                  <div class="overflow-x-auto">
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
                                  <div class="font-semibold text-left">Type</div>
                              </th>
                              <th class="p-2 whitespace-nowrap">
                                  <div class="font-semibold text-center">Status</div>
                              </th>
                              <th class="p-2 whitespace-nowrap">
                                  <div class="font-semibold text-left">Courier</div>
                              </th>
                              <th class="p-2 whitespace-nowrap">
                                  <div class="font-semibold text-center">Count of orders</div>
                              </th>
                              <th class="p-2 whitespace-nowrap">
                                  <div class="font-semibold text-center">Date</div>
                              </th>
                          </tr>
                          </thead>
                          <!-- Table body -->
                          <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60">
                          <tr v-for="task in tasks" :key="task.id">
                              <td class="p-2 whitespace-nowrap">
                                  <div class="flex items-center">
                                      <div class="font-medium text-gray-800 dark:text-gray-100">{{ task.id }}</div>
                                  </div>
                              </td>
                              <td class="p-2 whitespace-nowrap">
                                  <div class="flex items-center">
                                      <div class="font-medium text-gray-800 dark:text-gray-100">{{task.uuid}}</div>
                                  </div>
                              </td>
                              <td class="p-2 whitespace-nowrap">
                                  <div class="text-left font-medium text-green-500">{{task.status}}</div>
                              </td>
                              <td class="p-2 whitespace-nowrap">
                                  <div class="text-left">{{task.courierName}}</div>
                              </td>
                              <td class="p-2 whitespace-nowrap">
                                  <div class="text-left">{{task.countOrders}}</div>
                              </td>
                              <td class="p-2 whitespace-nowrap">
                                  <div class="text-left">{{task.updated_at}}</div>
                              </td>
                          </tr>
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
      </main>

      <Banner />

    </div>

  </div>
</template>

<script>
import { ref } from 'vue'
import Sidebar from '../partials/Sidebar.vue'
import Header from '../partials/Header.vue'
import FilterButton from '../components/DropdownFilter.vue'
import Banner from '../partials/Banner.vue'
import axios from "axios";

export default {
  name: 'Dashboard',
  components: {
    Sidebar,
    Header,
    FilterButton,
    Banner,
  },
    mounted() {
      axios.get('/api/tasks')
         .then((response) => {
           this.tasks = response.data.data
         })
    },
  setup() {
    const tasks = ref([])
    const sidebarOpen = ref(false)

    return {
      sidebarOpen,
        tasks
    }
  }
}
</script>
