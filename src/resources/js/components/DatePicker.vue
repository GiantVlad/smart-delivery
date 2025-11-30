<template>
  <div class="date-picker">
    <FormField :label="label" :help="help">
      <div class="relative">
        <input
          ref="dateInput"
          :value="modelValue"
          :placeholder="placeholder"
          class="form-input w-full pl-10"
          readonly
        />
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
          </svg>
        </span>
      </div>
    </FormField>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount, nextTick } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import FormField from './FormField.vue';

const props = defineProps({
  modelValue: {
    type: [String, Date, Array],
    default: null,
  },
  placeholder: {
    type: String,
    default: 'Select date',
  },
  label: {
    type: String,
    default: '',
  },
  help: {
    type: String,
    default: '',
  },
  options: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue', 'on-change']);

let datepicker = null;
const dateInput = ref(null);

const defaultOptions = {
  dateFormat: 'Y-m-d',
  allowInput: false,
  clickOpens: true,
  static: true,
  onChange: (selectedDates, dateStr) => {
    emit('update:modelValue', dateStr);
    emit('on-change', selectedDates, dateStr);
  },
};

// Merge default options with props
const mergedOptions = {
  ...defaultOptions,
  ...props.options,
  onChange: (selectedDates, dateStr) => {
    emit('update:modelValue', dateStr);
    emit('on-change', selectedDates, dateStr);
  }
};

onMounted(async () => {
  await nextTick();

  if (dateInput.value) {
    datepicker = flatpickr(dateInput.value, mergedOptions);

    if (props.modelValue) {
      datepicker.setDate(props.modelValue, false);
    }
  }
});

watch(() => props.modelValue, (newValue) => {
  if (datepicker && newValue !== datepicker.selectedDates[0]) {
    datepicker.setDate(newValue, false);
  }
});

watch(() => props.options, (newOptions) => {
  if (datepicker) {
    Object.entries(newOptions).forEach(([key, value]) => {
      datepicker.set(key, value);
    });
  }
}, { deep: true });

onBeforeUnmount(() => {
  if (datepicker) {
    datepicker.destroy();
    datepicker = null;
  }
});
</script>

<style scoped>
.date-picker {
  @apply w-full;
}

:deep(.form-input) {
  @apply bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pl-10 pr-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500;
}
</style>
