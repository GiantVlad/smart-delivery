<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: null,
  },
  id: {
    type: String,
    default: null,
  },
  placeholder: {
    type: String,
    default: 'Start typing an address',
  },
})

const emit = defineEmits(['update:modelValue'])

const wrapperEl = ref(null)
const loadError = ref('')

const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || ''
const autocompleteCountries = (import.meta.env.VITE_GOOGLE_MAPS_AUTOCOMPLETE_COUNTRIES || '')
  .split(',')
  .map((country) => country.trim().toUpperCase())
  .filter(Boolean)
const autocompleteBounds = import.meta.env.VITE_GOOGLE_MAPS_AUTOCOMPLETE_BOUNDS || ''
const autocompleteStrictBounds = import.meta.env.VITE_GOOGLE_MAPS_AUTOCOMPLETE_STRICT_BOUNDS === 'true'

let autocompleteElement = null
let selectListener = null
let inputListener = null

const loadGoogleMaps = () => new Promise((resolve, reject) => {
  if (window.google?.maps?.importLibrary) {
    resolve(window.google)
    return
  }

  if (!googleMapsApiKey) {
    reject(new Error('Missing VITE_GOOGLE_MAPS_API_KEY.'))
    return
  }

  const existingScript = document.getElementById('google-maps-places-script')
  if (existingScript) {
    existingScript.addEventListener('load', () => resolve(window.google), { once: true })
    existingScript.addEventListener('error', () => reject(new Error('Failed to load Google Maps.')), { once: true })
    return
  }

  const script = document.createElement('script')
  const params = new URLSearchParams({
    key: googleMapsApiKey,
    libraries: 'places',
    loading: 'async',
  })

  script.id = 'google-maps-places-script'
  script.src = `https://maps.googleapis.com/maps/api/js?${params.toString()}`
  script.async = true
  script.defer = true
  script.onload = () => resolve(window.google)
  script.onerror = () => reject(new Error('Failed to load Google Maps.'))
  document.head.appendChild(script)
})

const parseBoundsLiteral = () => {
  const parts = autocompleteBounds.split(',').map((part) => Number(part.trim()))

  if (parts.length !== 4 || parts.some((part) => !Number.isFinite(part))) {
    return null
  }

  const [south, west, north, east] = parts

  return {
    south,
    west,
    north,
    east,
  }
}

const clearSelection = () => {
  emit('update:modelValue', null)
}

const initAutocomplete = async () => {
  try {
    await loadGoogleMaps()

    const [{ PlaceAutocompleteElement }, { LatLngBounds }] = await Promise.all([
      window.google.maps.importLibrary('places'),
      window.google.maps.importLibrary('core'),
    ])

    autocompleteElement = new PlaceAutocompleteElement()
    autocompleteElement.id = props.id || undefined
    autocompleteElement.placeholder = props.placeholder

    if (autocompleteCountries.length) {
      autocompleteElement.includedRegionCodes = autocompleteCountries
    }

    const boundsLiteral = parseBoundsLiteral()
    if (boundsLiteral) {
      const bounds = new LatLngBounds(
        { lat: boundsLiteral.south, lng: boundsLiteral.west },
        { lat: boundsLiteral.north, lng: boundsLiteral.east },
      )
      autocompleteElement.locationBias = bounds
      if (autocompleteStrictBounds) {
        autocompleteElement.locationRestriction = bounds
      }
    }

    if (props.modelValue?.address) {
      autocompleteElement.value = props.modelValue.address
    }

    selectListener = async (event) => {
      const prediction = event?.placePrediction
      if (!prediction) {
        clearSelection()
        return
      }

      const place = prediction.toPlace()
      await place.fetchFields({
        fields: ['formattedAddress', 'location', 'id'],
      })

      if (!place.location) {
        clearSelection()
        return
      }

      emit('update:modelValue', {
        address: place.formattedAddress || autocompleteElement.value || '',
        lat: place.location.lat(),
        lng: place.location.lng(),
        placeId: place.id || null,
      })
    }

    inputListener = () => {
      clearSelection()
    }

    autocompleteElement.addEventListener('gmp-select', selectListener)
    autocompleteElement.addEventListener('input', inputListener)
    wrapperEl.value.appendChild(autocompleteElement)
  } catch (error) {
    loadError.value = error.message || 'Failed to load Google address autocomplete.'
  }
}

watch(() => props.modelValue, (value) => {
  if (!autocompleteElement) {
    return
  }

  autocompleteElement.value = value?.address || ''
})

onMounted(() => {
  initAutocomplete()
})

onBeforeUnmount(() => {
  if (autocompleteElement && selectListener) {
    autocompleteElement.removeEventListener('gmp-select', selectListener)
  }
  if (autocompleteElement && inputListener) {
    autocompleteElement.removeEventListener('input', inputListener)
  }
})
</script>

<template>
  <div>
    <div
      ref="wrapperEl"
      class="google-places-wrapper px-3 py-2 max-w-full focus-within:ring border-gray-700 rounded w-full h-12 border bg-white dark:bg-slate-800"
    />
    <p v-if="loadError" class="mt-1 text-xs text-red-500">
      {{ loadError }}
    </p>
  </div>
</template>

<style scoped>
.google-places-wrapper :deep(gmp-place-autocomplete) {
  width: 100%;
}
</style>
