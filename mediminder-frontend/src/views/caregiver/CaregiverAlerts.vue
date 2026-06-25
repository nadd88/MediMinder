<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
import { useSidebar } from '@/composables/useSidebar'

const { sidebarOpen, toggleSidebar, closeSidebar } = useSidebar()

const auth = useAuthStore()
const router = useRouter()

const activeFilter = ref('all')

const alerts = ref([
  {
    id: 1,
    patientName: 'Rosnah Mat',
    type: 'missed',
    title: 'Missed dose',
    message: 'Lisinopril 10mg was not taken at 8:00 AM.',
    time: '8:30 AM',
    date: 'Today',
    read: false,
  },
  {
    id: 2,
    patientName: 'Rosnah Mat',
    type: 'missed',
    title: 'Missed dose',
    message: 'Simvastatin 20mg was not taken at 9:00 PM yesterday.',
    time: '9:30 PM',
    date: 'Yesterday',
    read: false,
  },
  {
    id: 3,
    patientName: 'Zainab Ali',
    type: 'pending',
    title: 'Dose pending',
    message: 'Aspirin 100mg is due at 12:00 PM. Not yet acknowledged.',
    time: '12:00 PM',
    date: 'Today',
    read: false,
  },
  {
    id: 4,
    patientName: 'Ahmad Hamid',
    type: 'taken',
    title: 'All doses taken',
    message: 'Ahmad Hamid completed all scheduled doses for today.',
    time: '9:00 AM',
    date: 'Today',
    read: true,
  },
  {
    id: 5,
    patientName: 'Rosnah Mat',
    type: 'missed',
    title: 'Missed dose',
    message: 'Lisinopril 10mg was not taken at 8:00 AM.',
    time: '8:30 AM',
    date: '23 Jun',
    read: true,
  },
])

const filters = [
  { key: 'all',     label: 'All' },
  { key: 'missed',  label: 'Missed' },
  { key: 'pending', label: 'Pending' },
  { key: 'taken',   label: 'Taken' },
]

const filtered = computed(() =>
  activeFilter.value === 'all'
    ? alerts.value
    : alerts.value.filter(a => a.type === activeFilter.value)
)

const unreadCount = computed(() => alerts.value.filter(a => !a.read).length)

function markRead(alert) {
  alert.read = true
}

function markAllRead() {
  alerts.value.forEach(a => (a.read = true))
}

function initials(name) {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function typeStyle(type) {
  return {
    missed:  { dot: 'bg-red-500',    badge: 'bg-red-100 text-red-700',    avatar: 'bg-red-100 text-red-600' },
    pending: { dot: 'bg-amber-400',  badge: 'bg-amber-100 text-amber-700', avatar: 'bg-amber-100 text-amber-600' },
    taken:   { dot: 'bg-green-500',  badge: 'bg-green-100 text-green-700', avatar: 'bg-green-100 text-green-600' },
  }[type]
}

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Sidebar -->
    <AppSidebar :open="sidebarOpen" @close="closeSidebar">
      <template #nav-links>
        <router-link to="/caregiver"          @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/home-icon.png" width="20" class="inline-block mr-2" /> Dashboard
        </router-link>
        <router-link to="/caregiver/patients" @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/patient.png" width="20" class="inline-block mr-2" /> Patients
        </router-link>
        <router-link to="/caregiver/alerts"   @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/alert.png" width="20" class="inline-block mr-2" /> Alerts
        </router-link>
      </template>
    </AppSidebar>

    <div class="flex flex-col min-h-screen">

      <!-- Header -->
      <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 sticky top-0 z-10">
        <button @click="toggleSidebar" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-600" aria-label="Toggle sidebar">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="17" y2="6"/><line x1="3" y1="11" x2="17" y2="11"/><line x1="3" y1="16" x2="17" y2="16"/></svg>
        </button>
        <div class="flex items-center gap-2">
          <div>
            <p class="text-sm text-gray-500">Caregiver</p>
            <h1 class="text-base font-semibold text-gray-800">Alerts</h1>
          </div>
          <!-- Unread badge -->
          <span
            v-if="unreadCount > 0"
            class="bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"
          >{{ unreadCount }}</span>
        </div>
        <div class="ml-auto">
          <button
            v-if="unreadCount > 0"
            class="text-xs text-green-600 font-medium hover:text-green-800 mr-3"
            @click="markAllRead"
          >Mark all read</button>
          <button class="text-sm text-gray-600 hover:text-gray-800" @click="handleLogout">Sign out</button>
        </div>
      </header>

      <main class="px-4 py-4 max-w-lg mx-auto w-full">

        <!-- Filter tabs -->
        <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
          <button
            v-for="f in filters"
            :key="f.key"
            @click="activeFilter = f.key"
            class="px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
            :class="activeFilter === f.key
              ? 'bg-green-600 text-white'
              : 'bg-white text-gray-600 border border-gray-200 hover:border-green-300'"
          >{{ f.label }}</button>
        </div>

        <!-- Alert list -->
        <div class="space-y-3">
          <div
            v-for="alert in filtered"
            :key="alert.id"
            class="bg-white rounded-xl p-4 shadow-sm transition-opacity"
            :class="alert.read ? 'opacity-60' : ''"
            @click="markRead(alert)"
          >
            <div class="flex items-start gap-3">

              <!-- Avatar -->
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0"
                :class="typeStyle(alert.type).avatar"
              >{{ initials(alert.patientName) }}</div>

              <div class="flex-1 min-w-0">
                <!-- Top row -->
                <div class="flex items-center justify-between gap-2 mb-0.5">
                  <p class="font-semibold text-gray-900 text-sm truncate">{{ alert.patientName }}</p>
                  <p class="text-xs text-gray-400 whitespace-nowrap">{{ alert.date }}, {{ alert.time }}</p>
                </div>

                <!-- Type badge + title -->
                <div class="flex items-center gap-2 mb-1">
                  <span
                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                    :class="typeStyle(alert.type).badge"
                  >{{ alert.title }}</span>
                  <!-- Unread dot -->
                  <span
                    v-if="!alert.read"
                    class="w-2 h-2 rounded-full flex-shrink-0"
                    :class="typeStyle(alert.type).dot"
                  ></span>
                </div>

                <!-- Message -->
                <p class="text-sm text-gray-600">{{ alert.message }}</p>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="filtered.length === 0" class="text-center py-12 text-gray-400">
            <p class="text-sm">No {{ activeFilter === 'all' ? '' : activeFilter }} alerts.</p>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>
