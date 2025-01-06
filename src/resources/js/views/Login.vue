<script setup>
import { onMounted, reactive, ref } from 'vue'
import { mdiBallotOutline, mdiAccount } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import axios from "@/lib/axios.js"
import router from "@/router/index.js"
import CardBoxModal from "@/components/CardBoxModal.vue"

const form = reactive({
  email: null,
  password: unitTypes[1],
})

const submit = () => {
  axios.post('/api/login',
    {
      email: form.email,
      password: form.password,
    })
    .then((response) => {
      router.push({ path: 'tasks' })
    }).catch((e) => {console.log(e)})
}

const formStatusWithHeader = ref(true)

const formStatusCurrent = ref(0)

const formStatusOptions = ['info', 'success', 'danger', 'warning']

onMounted(async () => {
  axios.get('/sanctum/csrf-cookie').then(response => {
    console.log(response)
  });
})

const formStatusSubmit = () => {
  formStatusCurrent.value = formStatusOptions[formStatusCurrent.value + 1]
    ? formStatusCurrent.value + 1
    : 0
}

</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBallotOutline" title="Login" main>
      </SectionTitleLineWithButton>

      <CardBox form @submit.prevent="submit" :is-form="true">
        <FormField label="Email">
          <FormControl v-model="form.email"/>
        </FormField>
        <FormField label="Unit type">
          <FormControl v-model="form.password"/>
        </FormField>
        <BaseDivider />

        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Submit" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
