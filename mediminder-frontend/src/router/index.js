import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import PatientDashboard from '../views/patient/PatientDashboard.vue'
import DoseLogView from '../views/patient/DoseLogView.vue'
import AdheranceView from '../views/patient/AdheranceView.vue'
import CaregiverDashboard from '../views/caregiver/CaregiverDashboard.vue'


const routes = [
  { path: '/', component: LoginView },
  { path: '/patient', component: PatientDashboard },
  { path: '/caregiver', component: CaregiverDashboard },
  { path: '/patient/doses', component: DoseLogView },
  { path: '/patient/adherence', component: AdheranceView },
]


const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
