<script setup>

import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from './AdminLayout.vue'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()

const patients = ref([])
const medications = ref([])
const interactions = ref([])

const selectedPatientId = ref(null)
const loading = ref(true)     // initial patient/medication load
const saving = ref(false)
const checking = ref(false)   // interaction lookup in flight
const errors = reactive({})
const flash = reactive({ type: '', text: '' })

const today = new Date().toISOString().slice(0, 10)

const form = reactive({
  medication_id: '',
  dose: '1 tablet',
  frequency: 'Once daily',
  start_date: today,
  end_date: '',
  notes: '',
})

const selectedPatient = computed(() =>
  patients.value.find((p) => p.id === selectedPatientId.value),
)

const selectedMedication = computed(() =>
  medications.value.find((m) => m.id === Number(form.medication_id)),
)

// Block the save button until the required fields are filled in.
const canSubmit = computed(
  () =>
    !!selectedPatientId.value &&
    !!form.medication_id &&
    !!form.dose &&
    !!form.frequency &&
    !!form.start_date &&
    !saving.value,
)

function setFlash(type, text) {
  flash.type = type
  flash.text = text
}

function clearErrors() {
  Object.keys(errors).forEach((k) => delete errors[k])
}

// ---- data loading ----
async function loadInitial() {
  loading.value = true
  try {
    const [pRes, mRes] = await Promise.all([
      api.listPatients(),
      api.searchMedications(),
    ])
    patients.value = pRes.data.patients
    medications.value = mRes.data.medications

    // Pre-select patient from ?patient=ID (e.g. coming from the patient list),
    // otherwise fall back to the first patient.
    const fromQuery = Number(route.query.patient)
    selectedPatientId.value = fromQuery || patients.value[0]?.id || null
  } catch (e) {
    setFlash('error', 'Could not load patients or the drug catalogue.')
  } finally {
    loading.value = false
  }
}

// ---- live drug-interaction check ----
async function runInteractionCheck() {
  interactions.value = []
  if (!form.medication_id || !selectedPatientId.value) return
  checking.value = true
  try {
    const { data } = await api.checkInteractions(
      selectedPatientId.value,
      form.medication_id,
    )
    interactions.value = data.interactions
  } catch (e) {
    // A failed interaction check shouldn't block prescribing; just skip it.
    interactions.value = []
  } finally {
    checking.value = false
  }
}

// Re-check whenever the drug OR the patient changes.
watch(() => form.medication_id, runInteractionCheck)
watch(selectedPatientId, runInteractionCheck)

// ---- submit ----
async function submit({ stay = false } = {}) {
  clearErrors()
  setFlash('', '')

  if (!selectedPatientId.value) {
    setFlash('error', 'Please choose a patient first.')
    return
  }

  saving.value = true
  try {
    await api.createPrescription(selectedPatientId.value, {
      medication_id: form.medication_id,
      dose: form.dose,
      frequency: form.frequency,
      start_date: form.start_date,
      end_date: form.end_date || null,
      notes: form.notes,
    })

    if (stay) {
      // Reset the drug-specific fields but keep the patient selected,
      // so the admin can quickly add several prescriptions in a row.
      form.medication_id = ''
      form.notes = ''
      interactions.value = []
      setFlash('success', 'Prescription added. You can add another below.')
    } else {
      // Hand back to the prescriptions list for this patient.
      router.push({ path: '/admin/prescriptions', query: { patient: selectedPatientId.value } })
    }
  } catch (e) {
    if (e.response?.status === 422) {
      Object.assign(errors, e.response.data.errors || {})
      setFlash('error', 'Please fix the highlighted fields.')
    } else if (e.response?.status === 404) {
      setFlash('error', 'That patient no longer exists.')
    } else {
      setFlash('error', 'Something went wrong saving the prescription.')
    }
  } finally {
    saving.value = false
  }
}

function cancel() {
  router.push('/admin/prescriptions')
}

onMounted(loadInitial)
</script>

<template>
  <AdminLayout
    active="prescriptions"
    title="Add prescription"
    :subtitle="selectedPatient ? `for ${selectedPatient.name}` : 'Select a patient'"
  >
    <div class="mx-auto max-w-2xl">
      <!-- Flash banner -->
      <div
        v-if="flash.text"
        class="mb-5 rounded-lg px-4 py-3 text-sm"
        :class="flash.type === 'success'
          ? 'border border-emerald-300 bg-emerald-50 text-emerald-800'
          : 'border border-red-300 bg-red-50 text-red-700'"
      >
        {{ flash.text }}
      </div>

      <div v-if="loading" class="rounded-xl border border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-400">
        Loading patients and drug catalogue…
      </div>

      <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
        <!-- Patient -->
        <div class="mb-4">
          <label class="mb-1 block text-xs font-medium text-slate-500">Patient</label>
          <select
            v-model.number="selectedPatientId"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
          >
            <option :value="null" disabled>Select a patient…</option>
            <option v-for="p in patients" :key="p.id" :value="p.id">
              {{ p.name }} · {{ p.age ?? '—' }} yrs
            </option>
          </select>
        </div>

        <!-- Drug -->
        <div class="mb-4">
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

        <!-- Interaction warnings -->
        <div v-if="checking" class="mb-4 text-xs text-slate-400">Checking for interactions…</div>
        <div
          v-for="(it, i) in interactions"
          v-else
          :key="i"
          class="mb-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
          ⚠ Interaction with <strong>{{ it.other_medication }}</strong>
          ({{ it.severity }}) — {{ it.warning }}
        </div>

        <!-- Dose + frequency -->
        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Dose</label>
            <input
              v-model="form.dose"
              placeholder="e.g. 1 tablet"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              :class="errors.dose ? 'border-red-400' : ''"
            />
            <p v-if="errors.dose" class="mt-1 text-xs text-red-600">{{ errors.dose }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Frequency</label>
            <select
              v-model="form.frequency"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              :class="errors.frequency ? 'border-red-400' : ''"
            >
              <option>Once daily</option>
              <option>Twice daily</option>
              <option>Three times daily</option>
              <option>Once nightly</option>
              <option>As needed</option>
            </select>
            <p v-if="errors.frequency" class="mt-1 text-xs text-red-600">{{ errors.frequency }}</p>
          </div>
        </div>

        <!-- Dates -->
        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Start date</label>
            <input
              v-model="form.start_date"
              type="date"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              :class="errors.start_date ? 'border-red-400' : ''"
            />
            <p v-if="errors.start_date" class="mt-1 text-xs text-red-600">{{ errors.start_date }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">
              End date <span class="text-slate-400">(optional)</span>
            </label>
            <input
              v-model="form.end_date"
              type="date"
              :min="form.start_date"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              :class="errors.end_date ? 'border-red-400' : ''"
            />
            <p v-if="errors.end_date" class="mt-1 text-xs text-red-600">{{ errors.end_date }}</p>
          </div>
        </div>

        <!-- Notes -->
        <div class="mb-6">
          <label class="mb-1 block text-xs font-medium text-slate-500">
            Notes <span class="text-slate-400">(optional)</span>
          </label>
          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="e.g. Take with food"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
          <button
            :disabled="!canSubmit"
            class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
            @click="submit({ stay: false })"
          >
            {{ saving ? 'Saving…' : 'Save prescription' }}
          </button>
          <button
            :disabled="!canSubmit"
            class="rounded-lg border border-emerald-600 px-5 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-60"
            @click="submit({ stay: true })"
          >
            Save &amp; add another
          </button>
          <button
            class="ml-auto rounded-lg px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700"
            @click="cancel"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
