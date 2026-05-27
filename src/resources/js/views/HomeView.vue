<script setup>
import { computed, ref, onMounted } from 'vue'
import {
  mdiAccountMultiple,
  mdiCartOutline,
  mdiChartTimelineVariant,
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
const mapCouriers = ref([])
const mapError = ref('')
const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || ''

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

const loadMapCouriers = async () => {
  mapError.value = ''
  try {
    const response = await http.get('/api/dashboard/active-couriers-map')
    mapCouriers.value = response.data?.data || []
  } catch (error) {
    mapError.value = error.response?.data?.message || 'Failed to load courier map data.'
  }
}

onMounted(() => {
  fillChartData()
  loadMapCouriers()
})

const defaultCenter = { lat: 40.689247, lng: -74.044502 }
const mapMarkers = computed(() => mapCouriers.value
  .map((courier) => {
    const lat = Number(courier.lat)
    const lng = Number(courier.lng)

    return {
      ...courier,
      position: { lat, lng },
    }
  })
  .filter((courier) => Number.isFinite(courier.position.lat) && Number.isFinite(courier.position.lng)))
const mapCenter = computed(() => mapMarkers.value[0]?.position || defaultCenter)

</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <CardBox class="mb-6">
        <GoogleMap
          v-if="googleMapsApiKey"
          :api-key="googleMapsApiKey"
          class="rounded-xl overflow-hidden"
          style="width: 100%; height: 500px"
          :center="mapCenter"
          :zoom="mapMarkers.length ? 12 : 3"
        >
          <Marker
            v-for="(courier, idx) in mapMarkers"
            :key="courier.courierUuid"
            :options="{
              position: courier.position,
              label: String(idx + 1),
              title: `${courier.courierName} (${courier.pointType}): ${courier.pointAddress}`,
            }"
          />
        </GoogleMap>
        <NotificationBar v-else color="warning">
          Set VITE_GOOGLE_MAPS_API_KEY to show active couriers on the map.
        </NotificationBar>
        <NotificationBar v-if="mapError" color="danger">
          {{ mapError }}
        </NotificationBar>
        <NotificationBar v-else-if="googleMapsApiKey && !mapMarkers.length" color="info">
          No couriers with active on-task routes found.
        </NotificationBar>
      </CardBox>
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

      <CardBox class="mb-6" has-table>
        <TableSampleCustomers />
      </CardBox>

    </SectionMain>
  </LayoutAuthenticated>
</template>
