<script setup>
import { ref } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import { useSidebar } from '@/composables/useSidebar'
import { mockApi } from '../../api/mockClient'
import { onMounted } from 'vue'

const { sidebarOpen, toggleSidebar, closeSidebar } = useSidebar()

const medications = ref([])

async function loadDoses() {
  const response = await mockApi.getDashboardData()

  if (response.success) {
    medications.value = response.data.medications
  }
}

onMounted(loadDoses)

// Which dose is waiting for confirmation, and what action
const pending = ref(null) // will be { med, action } when a button is clicked

function askConfirm(med, action) {
  pending.value = { med, action }
}

async function confirmAction() {
  if (!pending.value) return

  await mockApi.markDose(
    pending.value.med.id,
    pending.value.action
  )

  pending.value.med.status = pending.value.action

  if (pending.value.action === 'taken') {
    pending.value.med.takenAt = new Date().toLocaleTimeString('en-MY', {
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  pending.value = null
}

function cancelAction() {
  pending.value = null
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <AppSidebar :open="sidebarOpen" @close="closeSidebar">
      <template #nav-links>
        <router-link
          to="/patient"
          @click="closeSidebar"
          class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900"
          active-class="font-bold text-green-700 bg-green-50"
        >
          Dashboard
        </router-link>

        <router-link
          to="/patient/doses"
          @click="closeSidebar"
          class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900"
          active-class="font-bold text-green-700 bg-green-50"
        >
          Doses
        </router-link>

        <router-link
          to="/patient/adherence"
          @click="closeSidebar"
          class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900"
          active-class="font-bold text-green-700 bg-green-50"
        >
          Adherence
        </router-link>
        <router-link
          to="/patient/medicine-supply"
          @click="closeSidebar"
          class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900"
          active-class="font-bold text-green-700 bg-green-50"
        >
          Supply
        </router-link>
      </template>
    </AppSidebar>

    <!-- Main content -->
    <div class="flex flex-col min-h-screen">
      <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 sticky top-0 z-10">
        <button
          @click="toggleSidebar"
          class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-600"
          aria-label="Toggle sidebar"
        >
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="3" y1="6" x2="17" y2="6"/>
            <line x1="3" y1="11" x2="17" y2="11"/>
            <line x1="3" y1="16" x2="17" y2="16"/>
          </svg>
        </button>
        <h2 class="text-base font-semibold text-gray-800">Dose log</h2>
      </header>

      <main class="px-4 py-4 max-w-lg mx-auto pb-24 md:pb-8 space-y-3">
      <div
        v-for="med in medications"
        :key="med.id"
        class="bg-white rounded-xl p-4 shadow-sm"
      >
        <div class="flex justify-between items-start mb-3">
          <div>
            <p class="font-medium text-gray-900">{{ med.name }}</p>
            <p class="text-sm text-gray-500">{{ med.dose }}</p>
            <p class="text-sm text-gray-400">{{ med.time }}</p>
          </div>

          <!-- Status badge -->
          <span
            class="text-xs font-medium px-2 py-1 rounded-full"
            :class="{
              'bg-green-100 text-green-700': med.status === 'taken',
              'bg-amber-100 text-amber-700': med.status === 'due',
              'bg-red-100 text-red-700': med.status === 'missed' || med.status === 'skipped',
              'bg-gray-100 text-gray-500': med.status === 'upcoming',
            }"
          >
            {{ med.status === 'taken' ? 'Taken' : med.status === 'due' ? 'Due now' : med.status === 'skipped' ? 'Skipped' : med.status === 'missed' ? 'Missed' : 'Upcoming' }}
          </span>
        </div>

        <!-- Taken confirmation line -->
        <p v-if="med.status === 'taken'" class="text-sm text-green-600">
          ✓ Logged at {{ med.takenAt }}
        </p>

        <!-- Mark Taken / Skip buttons (only when due or upcoming) -->
        <div v-if="med.status === 'due' || med.status === 'upcoming'" class="flex gap-2 mt-1">
          <button
            class="flex-1 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700"
            @click="askConfirm(med, 'taken')"
          >
            Mark taken
          </button>
          <button
            class="flex-1 py-2 rounded-lg bg-red-50 text-red-600 text-sm font-medium hover:bg-red-100"
            @click="askConfirm(med, 'skipped')"
          >
            Skip
          </button>
        </div>
      </div>
      </main>
    </div>

    <!-- Bottom nav -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex">
        <router-link
            to="/patient"
            class="flex-1 flex flex-col items-center py-3 text-xs text-gray-500"
            active-class="text-green-700 font-semibold"
        >
            <img src="@/assets/home-icon.png" alt="Home" width="24" height="24">
            Dashboard
        </router-link>
        <router-link
            to="/patient/doses"
            class="flex-1 flex flex-col items-center py-3 text-xs text-gray-500"
            active-class="text-green-700 font-semibold"
        >
            <img src="@/assets/pill-icon.png" alt="Doses" width="24" height="24">
            Doses
        </router-link>
        <router-link
            to="/patient/adherence"
            class="flex-1 flex flex-col items-center py-3 text-xs text-gray-500"
            active-class="text-green-700 font-semibold"
        >
            <img src="@/assets/adherence-icon.png" alt="Adherence" width="24" height="24">
            Adherence
        </router-link>
        <router-link
            to="/patient/medicine-supply"
            class="flex-1 flex flex-col items-center py-3 text-xs text-gray-500"
            active-class="text-green-700 font-semibold"
        >
            <img src="@/assets/supply.png" alt="Supply" width="24" height="24">
            Supply
        </router-link>
</nav>

    <!-- Confirmation modal (pops up when pending is not null) -->
    <div
      v-if="pending"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
    >
      <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-lg">
        <h2 class="font-semibold text-gray-900 mb-1">
          {{ pending.action === 'taken' ? 'Mark as taken?' : 'Skip this dose?' }}
        </h2>
        <p class="text-sm text-gray-500 mb-4">
          {{ pending.med.name }} will be logged as {{ pending.action }}.
        </p>
        <div class="flex gap-2">
          <button
            class="flex-1 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm"
            @click="cancelAction"
          >
            Cancel
          </button>
          <button
            class="flex-1 py-2 rounded-lg text-white text-sm font-medium"
            :class="pending.action === 'taken' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
            @click="confirmAction"
          >
            {{ pending.action === 'taken' ? 'Mark taken' : 'Skip dose' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>