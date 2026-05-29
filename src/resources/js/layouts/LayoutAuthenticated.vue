<script setup>
import { mdiForwardburger, mdiBackburger, mdiMenu } from '@mdi/js'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import menuAside from '@/menuAside.js'
import menuNavBar from '@/menuNavBar.js'
import { useDarkModeStore } from '@/stores/darkMode.js'
import BaseIcon from '@/components/BaseIcon.vue'
import NavBar from '@/components/NavBar.vue'
import NavBarItemPlain from '@/components/NavBarItemPlain.vue'
import AsideMenu from '@/components/AsideMenu.vue'
import FooterBar from '@/components/FooterBar.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import {useMainStore} from "@/stores/main.js";
import http from "@/lib/axios.js";

const layoutAsidePadding = 'xl:pl-60'

const darkModeStore = useDarkModeStore()

const router = useRouter()
const mainStore = useMainStore()
const isAsideMobileExpanded = ref(false)
const isAsideLgActive = ref(false)
const isProfileModalActive = ref(false)
const profile = ref({
  name: '',
  email: '',
  roles: [],
})
const profileLoading = ref(false)
const profileError = ref('')

router.beforeEach(() => {
  isAsideMobileExpanded.value = false
  isAsideLgActive.value = false
})

const menuClick = async (event, item) => {
  if (item.isToggleLightDark) {
    darkModeStore.set()
  }

  if (item.isProfile) {
    profileLoading.value = true
    profileError.value = ''
    http.get('/api/me')
      .then((response) => {
        profile.value = {
          name: response.data?.data?.name || '',
          email: response.data?.data?.email || '',
          roles: response.data?.data?.roles || [],
        }
        isProfileModalActive.value = true
      })
      .catch((error) => {
        profileError.value = error.response?.data?.message || 'Failed to load profile'
        isProfileModalActive.value = true
      })
      .finally(() => {
        profileLoading.value = false
      })
  }

  if (item.isLogout) {
    try {
      await http.post('/api/logout', {})
    } catch (error) {
      // Ignore logout API errors and enforce local logout.
    } finally {
      mainStore.clearStore()
      router.replace({ name: 'login' })
    }
  }
}
</script>

<template>
  <div
    :class="{
      'overflow-hidden lg:overflow-visible': isAsideMobileExpanded
    }"
  >
    <CardBoxModal
      v-model="isProfileModalActive"
      title="My Profile"
      button-label="Close"
      has-cancel
      @confirm="isProfileModalActive = false"
      @cancel="isProfileModalActive = false"
    >
      <div v-if="profileLoading">Loading profile...</div>
      <div v-else-if="profileError" class="text-red-600">{{ profileError }}</div>
      <div v-else class="space-y-2">
        <p><b>Name:</b> {{ profile.name || '-' }}</p>
        <p><b>Email:</b> {{ profile.email || '-' }}</p>
        <p><b>Roles:</b> {{ profile.roles.length ? profile.roles.join(', ') : 'No roles' }}</p>
      </div>
    </CardBoxModal>

    <div
      :class="[layoutAsidePadding, { 'ml-60 lg:ml-0': isAsideMobileExpanded }]"
      class="pt-14 min-h-screen w-screen transition-position lg:w-auto bg-gray-50 dark:bg-slate-800 dark:text-slate-100"
    >
      <NavBar
        :menu="menuNavBar"
        :class="[layoutAsidePadding, { 'ml-60 lg:ml-0': isAsideMobileExpanded }]"
        @menu-click="menuClick"
      >
        <NavBarItemPlain
          display="flex lg:hidden"
          @click.prevent="isAsideMobileExpanded = !isAsideMobileExpanded"
        >
          <BaseIcon :path="isAsideMobileExpanded ? mdiBackburger : mdiForwardburger" size="24" />
        </NavBarItemPlain>
        <NavBarItemPlain display="hidden lg:flex xl:hidden" @click.prevent="isAsideLgActive = true">
          <BaseIcon :path="mdiMenu" size="24" />
        </NavBarItemPlain>
      </NavBar>
      <AsideMenu
        :is-aside-mobile-expanded="isAsideMobileExpanded"
        :is-aside-lg-active="isAsideLgActive"
        :menu="menuAside"
        @menu-click="menuClick"
        @aside-lg-close-click="isAsideLgActive = false"
      />
      <slot />
      <FooterBar>
        Smart Delivery hofirma@gmail.com
      </FooterBar>
    </div>
  </div>
</template>
