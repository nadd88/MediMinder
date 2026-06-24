<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const props = defineProps({
  open: Boolean
})
const emit = defineEmits(['close'])
const auth = useAuthStore()
const router = useRouter()

function handleLogout() {
  auth.logout()
  emit('close')
  router.push('/login')
}
</script>

<template>
  <!-- Overlay (mobile only) -->
  <div
    v-if="open"
    class="fixed inset-0 bg-black/40 z-20 md:hidden"
    @click="emit('close')"
  />

  <!-- Sidebar -->
  <aside
    class="fixed top-0 left-0 h-full w-56 bg-white border-r border-gray-200 z-30 flex flex-col
           transform transition-transform duration-300 ease-in-out"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <!-- Header -->
    <div class="bg-green-700 px-5 py-5 flex items-start justify-between">
      <div>
        <img src="@/assets/MediMinder_Logo_White_v2.png" alt="MediMinder" width="200">
        <p class="text-green-100 text-xs mt-2">Good Morning, {{ auth.name }}</p>
      </div>
      <button
        @click="emit('close')"
        class="text-white/70 hover:text-white text-xl leading-none mt-0.5"
        aria-label="Close sidebar"
      >✕</button>
    </div>

    <!-- Nav links — swap these per role -->
    <nav class="flex flex-col gap-4 p-5 flex-1">
      <slot name="nav-links" />
    </nav>

    <!-- Footer / Logout -->
    <div class="p-4 border-t">
      <button
        @click="handleLogout"
        class="w-full flex items-center gap-2 text-left text-red-600 hover:bg-red-50 px-3 py-2 rounded-lg"
        aria-label="Log out"
      >
        Logout
      </button>
    </div>
  </aside>
</template>