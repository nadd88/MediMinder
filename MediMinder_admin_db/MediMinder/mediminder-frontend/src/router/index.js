import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import PatientDashboard from '../views/patient/PatientDashboard.vue'
import DoseLogView from '../views/patient/DoseLogView.vue'
import AdheranceView from '../views/patient/AdheranceView.vue'

// Admin module (Database & Security Lead deliverable).
import AdminPatients from '../views/admin/AdminPatients.vue'
import AdminPrescriptions from '../views/admin/AdminPrescriptions.vue'
import AdminReports from '../views/admin/AdminReports.vue'

import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/', component: LoginView },

  // ---- patient ----
  { path: '/patient', component: PatientDashboard },
  { path: '/patient/doses', component: DoseLogView },
  { path: '/patient/adherence', component: AdheranceView },

  // ---- admin (clinic administrator) ----
  { path: '/admin', redirect: '/admin/patients' },
  { path: '/admin/patients', component: AdminPatients, meta: { role: 'Admin' } },
  { path: '/admin/prescriptions', component: AdminPrescriptions, meta: { role: 'Admin' } },
  { path: '/admin/reports', component: AdminReports, meta: { role: 'Admin' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Route guard: protect role-restricted pages. Unauthenticated users are sent
// to the login screen; authenticated users without the required role are
// bounced back to the login screen as well (no data leakage to wrong role).
router.beforeEach((to) => {
  const auth = useAuthStore()
  const required = to.meta?.role

  if (required) {
    if (!auth.isAuthenticated) {
      return { path: '/' }
    }
    if (auth.role !== required) {
      return { path: '/' }
    }
  }
  return true
})

export default router
