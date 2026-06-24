<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from './AdminLayout.vue'


const route = useRoute()

const patients = ref([])
const selectedPatientId = ref(null)
const prescriptions = ref([])
const medications = ref([])
const interactions = ref([])
const loading = ref(false)
const saving = ref(false)
const errors = reactive({})
const flash = ref('')

const form = reactive({
  medication_id: '',
  dose: '1 tablet',
  frequency: 'Once daily',
  start_date: new Date().toISOString().slice(0, 10),
  end_date: '',
  notes: '',
})

const selectedPatient = computed(() =>
  patients.value.find((p) => p.id === selectedPatientId.value),
)

const statusStyles = {
  Active: 'bg-emerald-100 text-emerald-700',
  Ending: 'bg-amber-100 text-amber-700',
  Expired: 'bg-slate-200 text-slate-600',
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function loadPatients() {
  const { data } = await api.listPatients()
  patients.value = data.patients
  const fromQuery = Number(route.query.patient)
  selectedPatientId.value = fromQuery || patients.value[0]?.id || null
}

async function loadMedications() {
  const { data } = await api.searchMedications()
  medications.value = data.medications
}

async function loadPrescriptions() {
  if (!selectedPatientId.value) return
  loading.value = true
  try {
    const { data } = await api.listPrescriptions(selectedPatientId.value)
    prescriptions.value = data.prescriptions
  } finally {
    loading.value = false
  }
}

// Live interaction check when a drug is chosen.
watch(
  () => form.medication_id,
  async (medId) => {
    interactions.value = []
    if (!medId || !selectedPatientId.value) return
    const { data } = await api.checkInteractions(selectedPatientId.value, medId)
    interactions.value = data.interactions
  },
)

watch(selectedPatientId, loadPrescriptions)

async function submit() {
  Object.keys(errors).forEach((k) => delete errors[k])
  flash.value = ''
  saving.value = true
  try {
    await api.createPrescription(selectedPatientId.value, { ...form })
    flash.value = 'Prescription added.'
    form.medication_id = ''
    form.notes = ''
    interactions.value = []
    await loadPrescriptions()
  } catch (e) {
    if (e.response?.status === 422) {
      Object.assign(errors, e.response.data.errors || {})
    } else {
      flash.value = 'Something went wrong saving the prescription.'
    }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadPatients(), loadMedications()])
  await loadPrescriptions()
})
</script>

<template>
  <AdminLayout
    active="prescriptions"
    title="Prescriptions"
    :subtitle="selectedPatient ? selectedPatient.name : 'Select a patient'"
  >
    <!-- Patient selector -->
    <div class="mb-6 flex items-center gap-3">
      <label class="text-sm font-medium text-slate-600">Patient</label>
      <select
        v-model.number="selectedPatientId"
        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
      >
        <option v-for="p in patients" :key="p.id" :value="p.id">
          {{ p.name }} · {{ p.age ?? '—' }} yrs
        </option>
      </select>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
      <!-- Add prescription -->
      <section class="lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-4 text-sm font-semibold text-slate-900">Add new prescription</h2>

          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-500">Drug</label>
              <select
                v-model="form.medication_id"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                :class="errors.medication_id ? 'border-red-400' : ''"
              >
                <option value="" disabled>Search drug…</option>
                <option v-for="m in medications" :key="m.id" :value="m.id">
                  {{ m.name }} {{ m.strength }}
                </option>
              </select>
              <p v-if="errors.medication_id" class="mt-1 text-xs text-red-600">{{ errors.medication_id }}</p>
            </div>

            <!-- Interaction warning -->
            <div
              v-for="(it, i) in interactions"
              :key="i"
              class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800"
            >
              ⚠ Interaction detected with {{ it.other_medication }} ({{ it.severity }}) — {{ it.warning }}
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Dose</label>
                <input
                  v-model="form.dose"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                  :class="errors.dose ? 'border-red-400' : ''"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Frequency</label>
                <select
                  v-model="form.frequency"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                >
                  <option>Once daily</option>
                  <option>Twice daily</option>
                  <option>Three times daily</option>
                  <option>Once nightly</option>
                  <option>As needed</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Start date</label>
                <input
                  v-model="form.start_date"
                  type="date"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                  :class="errors.start_date ? 'border-red-400' : ''"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">End date</label>
                <input
                  v-model="form.end_date"
                  type="date"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-500">Notes</label>
              <input
                v-model="form.notes"
                placeholder="e.g. Take with food"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              />
            </div>

            <button
              :disabled="saving"
              class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
              @click="submit"
            >
              {{ saving ? 'Saving…' : 'Save prescription' }}
            </button>
            <p v-if="flash" class="text-center text-xs text-emerald-700">{{ flash }}</p>
          </div>
        </div>
      </section>

      <!-- Active prescriptions table -->
      <section class="lg:col-span-3">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <div class="border-b border-slate-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Active prescriptions</h2>
          </div>

          <div v-if="loading" class="px-5 py-6 text-sm text-slate-400">Loading…</div>
          <table v-else class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-5 py-2.5 font-semibold">Medication</th>
                <th class="px-5 py-2.5 font-semibold">Dose</th>
                <th class="px-5 py-2.5 font-semibold">Frequency</th>
                <th class="px-5 py-2.5 font-semibold">Start</th>
                <th class="px-5 py-2.5 font-semibold">End</th>
                <th class="px-5 py-2.5 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="rx in prescriptions" :key="rx.id" class="hover:bg-slate-50">
                <td class="px-5 py-3 font-medium text-slate-900">
                  {{ rx.medication_name }} {{ rx.strength }}
                </td>
                <td class="px-5 py-3 text-slate-600">{{ rx.dose }}</td>
                <td class="px-5 py-3 text-slate-600">{{ rx.frequency }}</td>
                <td class="px-5 py-3 text-slate-600">{{ fmtDate(rx.start_date) }}</td>
                <td class="px-5 py-3 text-slate-600">{{ fmtDate(rx.end_date) }}</td>
                <td class="px-5 py-3">
                  <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="statusStyles[rx.status] || 'bg-slate-100 text-slate-600'"
                  >{{ rx.status }}</span>
                </td>
              </tr>
              <tr v-if="!prescriptions.length">
                <td colspan="6" class="px-5 py-6 text-center text-sm text-slate-400">
                  No prescriptions yet for this patient.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>
