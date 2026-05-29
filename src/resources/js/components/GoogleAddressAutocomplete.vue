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

const ensureImportLibrary = async () => {
  if (window.google?.maps?.importLibrary) {
    return
  }

  if (!googleMapsApiKey) {
    throw new Error('Missing VITE_GOOGLE_MAPS_API_KEY.')
  }

  const globalGoogle = window.google || (window.google = {})
  const maps = globalGoogle.maps || (globalGoogle.maps = {})

  if (maps.importLibrary) {
    return
  }

  await new Promise((resolve, reject) => {
    const loadedLibraries = new Set()
    const params = new URLSearchParams()
    let scriptLoadingPromise = null

    const loadScript = () => {
      if (!scriptLoadingPromise) {
        scriptLoadingPromise = new Promise((innerResolve, innerReject) => {
          const script = document.createElement('script')

          params.set('libraries', [...loadedLibraries].join(','))
          params.set('key', googleMapsApiKey)
          params.set('v', 'beta')
          params.set('loading', 'async')
          params.set('callback', 'google.maps.__ib__')

          script.id = 'google-maps-import-library-shim'
          script.src = `https://maps.googleapis.com/maps/api/js?${params.toString()}`
          script.onerror = () => innerReject(new Error('Failed to load Google Maps.'))
          maps.__ib__ = innerResolve
          document.head.appendChild(script)
        })
      }

      return scriptLoadingPromise
    }

    maps.importLibrary = (libraryName, ...args) => {
      loadedLibraries.add(libraryName)

      return loadScript().then(() => maps.importLibrary(libraryName, ...args))
    }

    maps.importLibrary('core').then(resolve).catch(reject)
  })
}

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
    await ensureImportLibrary()

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

    if (wrapperEl.value) {
      wrapperEl.value.replaceChildren()
      wrapperEl.value.appendChild(autocompleteElement)
    }
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
  if (wrapperEl.value) {
    wrapperEl.value.replaceChildren()
  }
})
</script>

<template>
  <div>
    <div ref="wrapperEl" class="google-places-wrapper" />
    <p v-if="loadError" class="mt-1 text-xs text-red-500">
      {{ loadError }}
    </p>
  </div>
</template>

<style scoped>
.google-places-wrapper :deep(gmp-place-autocomplete) {
  display: block;
  width: 100%;
  min-height: 3rem;
  border: 1px solid rgb(55 65 81);
  border-radius: 0.25rem;
  background: white;
}

.google-places-wrapper:focus-within {
  box-shadow: 0 0 0 2px rgb(59 130 246 / 0.35);
  border-radius: 0.25rem;
}

:global(.dark) .google-places-wrapper :deep(gmp-place-autocomplete) {
  background: rgb(30 41 59);
}

.google-places-wrapper :deep(gmp-place-autocomplete input) {
  width: 100%;
  min-height: 3rem;
  border: none;
  outline: none;
  box-shadow: none;
  background: transparent;
}
</style>
