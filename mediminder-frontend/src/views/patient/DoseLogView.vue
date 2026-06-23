<script setup>
import { ref } from 'vue'

//Will be replaced with real API data later
const medications = ref([
  { id: 1, name: 'Metformin 500mg', dose: '1 tablet with food', time: '8:00 AM', status: 'taken', takenAt: '8:12 AM' },
  { id: 2, name: 'Lisinopril 10mg', dose: '1 tablet before meal', time: '12:00 PM', status: 'due' },
  { id: 3, name: 'Aspirin 75mg', dose: '1 tablet after meal', time: '9:00 PM', status: 'upcoming' },
])

// Which dose is waiting for confirmation, and what action
const pending = ref(null) // will be { med, action } when a button is clicked

function askConfirm(med, action) {
  pending.value = { med, action }
}

function confirmAction() {
  if (!pending.value) return
  pending.value.med.status = pending.value.action  // 'taken' or 'skipped'
  if (pending.value.action === 'taken') {
    pending.value.med.takenAt = new Date().toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' })
  }
  pending.value = null
}

function cancelAction() {
  pending.value = null
}
</script>

<template>
  <div class="min-h-screen bg-green-50">
    <header class="bg-green-700 text-white px-4 py-4">
      <h1 class="text-xl font-bold">Dose log</h1>
      <p class="text-sm opacity-80">{{ new Date().toDateString() }}</p>
    </header>

    <main class="px-4 py-4 max-w-lg mx-auto pb-20 space-y-3">
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

    <!-- Bottom nav -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex">
        <router-link
            to="/patient"
            class="flex-1 flex flex-col items-center py-3 text-xs"
            active-class="text-green-700 font-semibold"
        >
            <span class="text-lg"><img src="@/assets/home-icon.png" alt="Home" width="24" height="24"></span>
            Dashboard
        </router-link>
        <router-link
            to="/patient/doses"
            class="flex-1 flex flex-col items-center py-3 text-xs"
            active-class="text-green-700 font-semibold"
        >
            <span class="text-lg"><img src="@/assets/pill-icon.png" alt="Doses" width="24" height="24"></span>
            Doses
        </router-link>
        <router-link
            to="/patient/adherence"
            class="flex-1 flex flex-col items-center py-3 text-xs"
            active-class="text-green-700 font-semibold"
        >
            <span class="text-lg"><img src="@/assets/adherence-icon.png" alt="Adherence" width="24" height="24"></span>
            Adherence
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