import mockData from '../assets/mockData.json'

const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms))

const medications = mockData.medications.map((med) => ({
  ...med,
  status: med.status === 'due' ? 'pending' : med.status,
  remaining: med.remaining ?? (med.id === 2 ? 4 : 14),
  dailyDose: med.dailyDose ?? 1,
  totalPack: med.totalPack ?? 30,
  lastRefill: med.lastRefill ?? '7 days ago',
}))

function summary() {
  return {
    adherence7day: 87,
    dueToday: medications.filter((med) => med.status === 'pending').length,
    missedToday: medications.filter((med) => med.status === 'missed').length,
  }
}

export const mockApi = {
  async getDashboardData() {
    await delay(250)
    return {
      success: true,
      data: {
        summary: summary(),
        medications,
        patient: mockData.patient,
      },
    }
  },

  async getDoses() {
    await delay(250)
    return {
      success: true,
      data: { medications },
    }
  },

  async markDose(medicationId, status) {
    await delay(200)
    const medication = medications.find(m => m.id === medicationId)
    if (medication) {
      medication.status = status === 'skipped' ? 'missed' : status
      medication.takenAt = status === 'taken'
        ? new Date().toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' })
        : null
      if (status === 'taken') {
        medication.remaining = Math.max((medication.remaining ?? 0) - 1, 0)
      }
    }

    return {
      success: true,
      message: `Dose marked as ${status}`,
      data: { medicationId, status, takenAt: medication?.takenAt },
    }
  },

  async getAdherence(range = '7') {
    await delay(250)
    const isThirty = range === '30'
    return {
      success: true,
      data: {
        overall: 87,
        labels: isThirty ? ['Week 1', 'Week 2', 'Week 3', 'Week 4'] : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        values: isThirty ? [82, 75, 90, 87] : [80, 100, 60, 85, 100, 70, 87],
        byMedication: [
          { name: 'Metformin 500mg', percent: 95 },
          { name: 'Lisinopril 10mg', percent: 80 },
          { name: 'Aspirin 75mg', percent: 60 },
        ],
      },
    }
  },

  async getSupply() {
    await delay(250)
    return {
      success: true,
      data: medications.map((med) => ({
        id: med.id,
        name: med.name,
        dose: med.dose,
        remaining: med.remaining ?? 10,
        dailyDose: med.dailyDose ?? 1,
        totalPack: med.totalPack ?? 30,
        lastRefill: med.lastRefill ?? '7 days ago',
      })),
    }
  },

  async refillSupply(medicationId, amount) {
    await delay(200)
    const medication = medications.find(m => m.id === medicationId)
    if (medication) {
      medication.remaining = (medication.remaining ?? 0) + Number(amount)
      medication.lastRefill = 'Today'
    }
    return { success: true }
  },
}
