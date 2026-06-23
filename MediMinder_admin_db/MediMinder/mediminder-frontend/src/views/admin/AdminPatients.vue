<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from './AdminLayout.vue'
import api from '../../services/api'

const router = useRouter()
const patients = ref([])
const loading = ref(true)
const error = ref('')

function initials(name) {
  return name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase()
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.listPatients()
    patients.value = data.patients
  } catch (e) {
    error.value = 'Could not load patients. Is the API running?'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <AdminLayout active="patients" title="All patients" subtitle="Manage patient accounts and prescriptions">
    <div v-if="loading" class="text-slate-400">Loading patients…</div>
    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
      {{ error }}
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-5 py-3 font-semibold">Patient</th>
            <th class="px-5 py-3 font-semibold">Email</th>
            <th class="px-5 py-3 font-semibold">Age</th>
            <th class="px-5 py-3 font-semibold">Prescriptions</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="p in patients" :key="p.id" class="hover:bg-slate-50">
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                  {{ initials(p.name) }}
                </span>
                <span class="font-medium text-slate-900">{{ p.name }}</span>
              </div>
            </td>
            <td class="px-5 py-3 text-slate-500">{{ p.email }}</td>
            <td class="px-5 py-3 text-slate-600">{{ p.age ?? '—' }}</td>
            <td class="px-5 py-3 text-slate-600">{{ p.prescription_count }}</td>
            <td class="px-5 py-3 text-right">
              <button
                class="mr-2 rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                @click="router.push(`/admin/prescriptions?patient=${p.id}`)"
              >Prescriptions</button>
              <button
                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                @click="router.push(`/admin/reports?patient=${p.id}`)"
              >Report</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>
