<script setup>
import { onMounted, reactive, ref } from 'vue'
import { mdiBallotOutline } from '@mdi/js'
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

const form = reactive({
  email: null,
  password: null,
  password_conf: null,
})

const users = ref([])

const submit = () => {
  axios.post('/api/register',
    {
      email: form.email,
      password: form.password,
      password_confirmation: form.password_conf,
    })
    .then((response) => {
      router.push({ path: 'tasks' })
    }).catch((e) => {console.log(e)})
}

const formStatusWithHeader = ref(true)

const formStatusCurrent = ref(0)

const formStatusOptions = ['info', 'success', 'danger', 'warning']

onMounted(async () => {
  axios.get('/api/users').then((response) => {
    users.value = response.data.data
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
      <SectionTitleLineWithButton :icon="mdiBallotOutline" title="Registration" main>
      </SectionTitleLineWithButton>

      <CardBox form @submit.prevent="submit" :is-form="true">
        <FormField label="Name">
          <FormControl v-model="form.name"/>
        </FormField>
        <FormField label="Email">
          <FormControl v-model="form.email"/>
        </FormField>
        <FormField label="Password">
          <FormControl v-model="form.password"/>
        </FormField>
        <FormField label="Password">
          <FormControl v-model="form.password_conf"/>
        </FormField>
        <BaseDivider />

        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Add user" />
          </BaseButtons>
        </template>
      </CardBox>
      <CardBox class="mb-6 max-w-5xl" has-table v-if="users.length > 0">
        <!-- Table -->
        <table class="table-auto w-full">
          <!-- Table header -->
          <thead class="text-xs font-semibold uppercase dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50">
          <tr>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">#</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-left">Name</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-center">Email</div>
            </th>
            <th class="p-2 whitespace-nowrap">
              <div class="font-semibold text-center">Updates at</div>
            </th>
          </tr>
          </thead>
          <!-- Table body -->
          <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60">
          <tr v-for="user in users" :key="user.id">
            <td class="p-2 whitespace-nowrap">
              <div class="flex items-center">
                <div class="font-medium text-gray-800 dark:text-gray-100">{{ user.id }}</div>
              </div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="flex items-center">
                <div class="font-medium text-gray-800 dark:text-gray-100">{{user.name}}</div>
              </div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{user.email}}</div>
            </td>
            <td class="p-2 whitespace-nowrap">
              <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{user.updated_at}}</div>
            </td>
          </tr>
          </tbody>
        </table>
      </CardBox>

    </SectionMain>
  </LayoutAuthenticated>
</template>
