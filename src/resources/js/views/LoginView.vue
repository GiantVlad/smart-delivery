<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { mdiAccount, mdiAsterisk } from '@mdi/js'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormCheckRadio from '@/components/FormCheckRadio.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import http from "@/lib/axios.js";
import { useMainStore } from "@/stores/main.js";

const form = reactive({
  email: null,
  password: null,
  remember: true
})
const errorMessage = reactive({ value: '' })

const router = useRouter()
const mainStore = useMainStore()

const submit = async () => {
  errorMessage.value = ''
  await http.get('/sanctum/csrf-cookie')
  http.post('/api/login', {
    email: form.email,
    password: form.password,
    remember: form.remember,
  })
  .then((response) => {
    if (response.data && response.data.data) {
      mainStore.setUser({
        name: response.data.data.name,
        email: response.data.data.email
      })

      const redirectPath = router.currentRoute.value.query.redirect || '/orders'
      router.push(redirectPath)
    } else {
      errorMessage.value = 'Unexpected server response.'
    }
  })
  .catch((error) => {
    errorMessage.value = error.response?.data?.message || 'Unable to sign in.'
  })
}
</script>

<template>
  <LayoutGuest>
    <SectionFullScreen v-slot="{ cardClass }" bg="purplePink">
      <CardBox :class="cardClass" is-form @submit.prevent="submit">
        <FormField label="Login" help="Please enter your email">
          <FormControl
            v-model="form.email"
            :icon="mdiAccount"
            name="login"
            autocomplete="username"
          />
        </FormField>

        <FormField label="Password" help="Please enter your password">
          <FormControl
            v-model="form.password"
            :icon="mdiAsterisk"
            type="password"
            name="password"
            autocomplete="current-password"
          />
        </FormField>
        <p v-if="errorMessage.value" class="text-sm text-red-600 mb-2">{{ errorMessage.value }}</p>

        <FormCheckRadio
          v-model="form.remember"
          name="remember"
          label="Remember"
          :input-value="true"
        />

        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Login" />
            <BaseButton to="/dashboard" color="info" outline label="Back" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
