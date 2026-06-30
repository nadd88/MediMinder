import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import PatientDashboard from '../views/patient/PatientDashboard.vue'
import DoseLogView from '../views/patient/DoseLogView.vue'
import AdheranceView from '../views/patient/AdheranceView.vue'
import CaregiverDashboard from '../views/caregiver/CaregiverDashboard.vue'
import AddPrescription from '../views/admin/AddPrescription.vue'
import AdminLayout from "../views/admin/AdminLayout.vue"
import AdherenceReport from "../views/admin/AdherenceReport.vue"
import RegisterView from '../views/RegisterView.vue'
import CaregiverAlerts from '../views/caregiver/CaregiverAlerts.vue'
import CaregiverPatients from '../views/caregiver/CaregiverPatients.vue'
import PatientMedicineSupply from '../views/patient/PatientMedicineSupply.vue'


const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', component: LoginView },
  { path: '/patient', component: PatientDashboard },
  { path: '/caregiver', component: CaregiverDashboard },
  { path: '/patient/doses', component: DoseLogView },
  { path: '/patient/adherence', component: AdheranceView },
  { path: '/admin/add-prescription', component: AddPrescription },
  { path: '/admin/adherence-report', component: AdherenceReport },
  { path: '/admin', component: AdminLayout },
  { path: '/register' , component: RegisterView },
  { path: '/caregiver/alerts' , component: CaregiverAlerts },
  { path: '/caregiver/patients' , component: CaregiverPatients },
  { path: '/patient/medicine-supply', component: PatientMedicineSupply }
]


const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
