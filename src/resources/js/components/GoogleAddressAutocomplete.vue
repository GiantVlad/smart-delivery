<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

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

const inputEl = ref(null)
const inputValue = ref(props.modelValue?.address || '')
const loadError = ref('')
let autocomplete = null
let placeChangedListener = null
let AutocompleteClass = null
let LatLngBoundsClass = null

const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || ''
const autocompleteCountries = (import.meta.env.VITE_GOOGLE_MAPS_AUTOCOMPLETE_COUNTRIES || '')
  .split(',')
  .map((country) => country.trim())
  .filter(Boolean)
const autocompleteBounds = import.meta.env.VITE_GOOGLE_MAPS_AUTOCOMPLETE_BOUNDS || ''
const autocompleteStrictBounds = import.meta.env.VITE_GOOGLE_MAPS_AUTOCOMPLETE_STRICT_BOUNDS === 'true'

const inputClass = computed(() => [
  'px-3 py-2 max-w-full focus:ring focus:outline-none border-gray-700 rounded w-full h-12 border',
  'dark:placeholder-gray-400 bg-white dark:bg-slate-800',
])

const parseBounds = () => {
  const parts = autocompleteBounds.split(',').map((part) => Number(part.trim()))

  if (parts.length !== 4 || parts.some((part) => !Number.isFinite(part))) {
    return null
  }

  const [south, west, north, east] = parts

  return new LatLngBoundsClass(
    { lat: south, lng: west },
    { lat: north, lng: east },
  )
}

const loadPlacesLibrary = async () => {
  if (AutocompleteClass && LatLngBoundsClass) {
    return window.google
  }

  if (window.google?.maps?.importLibrary) {
    const coreLibrary = await window.google.maps.importLibrary('core')
    const placesLibrary = await window.google.maps.importLibrary('places')

    AutocompleteClass = placesLibrary.Autocomplete
    LatLngBoundsClass = coreLibrary.LatLngBounds
  } else {
    AutocompleteClass = window.google?.maps?.places?.Autocomplete
    LatLngBoundsClass = window.google?.maps?.LatLngBounds
  }

  if (!AutocompleteClass) {
    throw new Error('Google Places library is not available.')
  }

  if (!LatLngBoundsClass) {
    throw new Error('Google Maps bounds library is not available.')
  }

  return window.google
}

const loadGoogleMaps = () => new Promise((resolve, reject) => {
  if (window.google?.maps?.importLibrary) {
    loadPlacesLibrary().then(resolve).catch(reject)
    return
  }

  if (!googleMapsApiKey) {
    reject(new Error('Missing VITE_GOOGLE_MAPS_API_KEY.'))
    return
  }

  const existingScript = document.getElementById('google-maps-places-script')
  if (existingScript) {
    existingScript.addEventListener('load', () => loadPlacesLibrary().then(resolve).catch(reject), { once: true })
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
  script.onload = () => loadPlacesLibrary().then(resolve).catch(reject)
  script.onerror = () => reject(new Error('Failed to load Google Maps.'))
  document.head.appendChild(script)
})

const clearSelectedPlace = () => {
  emit('update:modelValue', null)
}

const initAutocomplete = async () => {
  try {
    await loadGoogleMaps()

    const options = {
      fields: ['formatted_address', 'geometry', 'name', 'place_id'],
      strictBounds: autocompleteStrictBounds,
    }
    const bounds = parseBounds()

    if (bounds) {
      options.bounds = bounds
    }

    if (autocompleteCountries.length) {
      options.componentRestrictions = { country: autocompleteCountries }
    }

    autocomplete = new AutocompleteClass(inputEl.value, options)
    placeChangedListener = autocomplete.addListener('place_changed', () => {
      const place = autocomplete.getPlace()
      const location = place.geometry?.location

      if (!location) {
        clearSelectedPlace()
        return
      }

      const address = place.formatted_address || place.name || inputValue.value
      inputValue.value = address

      emit('update:modelValue', {
        address,
        lat: location.lat(),
        lng: location.lng(),
        placeId: place.place_id || null,
      })
    })
  } catch (error) {
    loadError.value = error.message || 'Failed to load Google address autocomplete.'
  }
}

watch(() => props.modelValue, (value) => {
  inputValue.value = value?.address || ''
})

onMounted(() => {
  initAutocomplete()
})

onBeforeUnmount(() => {
  if (placeChangedListener) {
    window.google?.maps?.event?.removeListener(placeChangedListener)
  }
})
</script>

<template>
  <div>
    <input
      :id="id"
      ref="inputEl"
      v-model="inputValue"
      type="text"
      :placeholder="placeholder"
      :class="inputClass"
      autocomplete="off"
      @input="clearSelectedPlace"
    />
    <p v-if="loadError" class="mt-1 text-xs text-red-500">
      {{ loadError }}
    </p>
  </div>
</template>
