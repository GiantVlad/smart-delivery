<script setup>
import { ref, onMounted } from 'vue'
import {
  mdiAccountMultiple,
  mdiCartOutline,
  mdiChartTimelineVariant,
  mdiMonitorCellphone,
  mdiReload,
  mdiChartPie
} from '@mdi/js'
import LineChart from '@/components/Charts/LineChart.vue'
import SectionMain from '@/components/SectionMain.vue'
import CardBoxWidget from '@/components/CardBoxWidget.vue'
import CardBox from '@/components/CardBox.vue'
import { GoogleMap, Marker } from 'vue3-google-map'
import NotificationBar from '@/components/NotificationBar.vue'
import BaseButton from '@/components/BaseButton.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import TableSampleCustomers from '@/components/TableSampleCustomers.vue'
import http from '@/lib/axios.js'

const chartData = ref(null)
const chartError = ref('')

const chartPalette = [
  '#2563EB',
  '#DC2626',
  '#059669',
  '#D97706',
  '#7C3AED',
  '#0F766E',
  '#B91C1C',
  '#4F46E5',
]

const fillChartData = async () => {
  chartError.value = ''
  try {
    const response = await http.get('/api/dashboard/delivered-orders-trend')
    const payload = response.data?.data
    const labels = payload?.labels || []
    const datasets = (payload?.datasets || []).map((dataset, idx) => {
      const color = chartPalette[idx % chartPalette.length]
      return {
        label: dataset.label,
        data: dataset.data,
        fill: false,
        borderColor: color,
        backgroundColor: color,
        borderWidth: 2,
        pointBackgroundColor: color,
        pointBorderColor: '#ffffff',
        pointRadius: 3,
        pointHoverRadius: 5,
        tension: 0.3,
      }
    })

    chartData.value = { labels, datasets }
  } catch (error) {
    chartError.value = error.response?.data?.message || 'Failed to load chart data.'
  }
}

onMounted(() => {
  fillChartData()
})

const center = { lat: 40.689247, lng: -74.044502 }

</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <GoogleMap
        api-key="AIzaSyDF6ZTPdgnpG9wp_gRRWWWfI-QLI0pw1Tg"
        style="width: 100%; height: 500px"
        :center="center"
        :zoom="15"
      >
        <Marker :options="{ position: center }" />
      </GoogleMap>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
        <CardBoxWidget
          trend="12%"
          trend-type="up"
          color="text-emerald-500"
          :icon="mdiAccountMultiple"
          :number="512"
          label="Customers"
        />
        <CardBoxWidget
          trend="12%"
          trend-type="down"
          color="text-blue-500"
          :icon="mdiCartOutline"
          :number="7770"
          prefix="$"
          label="Sales"
        />
        <CardBoxWidget
          trend="Overflow"
          trend-type="alert"
          color="text-red-500"
          :icon="mdiChartTimelineVariant"
          :number="256"
          suffix="%"
          label="Performance"
        />
      </div>

      <SectionTitleLineWithButton :icon="mdiChartPie" title="Trends overview">
        <BaseButton :icon="mdiReload" color="whiteDark" @click="fillChartData" />
      </SectionTitleLineWithButton>

      <CardBox class="mb-6">
        <div v-if="chartData">
          <line-chart :data="chartData" class="h-96" />
        </div>
        <NotificationBar v-if="chartError" color="danger">
          {{ chartError }}
        </NotificationBar>
      </CardBox>

      <SectionTitleLineWithButton :icon="mdiAccountMultiple" title="Customers" />

      <NotificationBar color="info" :icon="mdiMonitorCellphone">
        <b>Responsive table.</b> Collapses on mobile
      </NotificationBar>
      <CardBox class="mb-6" has-table>
        <TableSampleCustomers />
      </CardBox>

    </SectionMain>
  </LayoutAuthenticated>
</template>
