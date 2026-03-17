import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/login/LoginView.vue'),
      meta: { requiresAuth: false }
    },
    {
      path: '/',
      component: () => import('@/layouts/DefaultLayout.vue'),
      meta: { requiresAuth: true },
      redirect: '/dashboard',
      children: [
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/views/dashboard/DashboardView.vue'),
          meta: { title: '控制台', icon: 'Odometer' }
        },
        // ── 比赛管理 ──
        {
          path: 'competition/rules',
          name: 'competition-rules',
          component: () => import('@/views/competition/RulesView.vue'),
          meta: { title: '比赛规则', icon: 'Document' }
        },
        {
          path: 'competition/registrations',
          name: 'competition-registrations',
          component: () => import('@/views/competition/RegistrationsView.vue'),
          meta: { title: '报名记录', icon: 'List' }
        },
        // ── 系统管理 ──
        {
          path: 'system/dict',
          name: 'system-dict',
          component: () => import('@/views/system/DictView.vue'),
          meta: { title: '字典管理', icon: 'Collection' }
        },
        {
          path: 'competition/fee-settings',
          name: 'competition-fee-settings',
          component: () => import('@/views/competition/FeeSettingsView.vue'),
          meta: { title: '费用设置', icon: 'Money' }
        },
        {
          path: 'bill/stats',
          name: 'bill-stats',
          component: () => import('@/views/bill/BillStatsView.vue'),
          meta: { title: '账单统计', icon: 'Wallet' }
        },
        // ── 公告管理 ──
        {
          path: 'announcements',
          name: 'announcements',
          component: () => import('@/views/announcement/AnnouncementView.vue'),
          meta: { title: '公告管理', icon: 'Bell' }
        },
        // ── 用户管理 ──
        {
          path: 'users',
          name: 'users',
          component: () => import('@/views/user/UserListView.vue'),
          meta: { title: '用户管理', icon: 'User' }
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore()
  if (to.meta.requiresAuth !== false && !authStore.isLoggedIn) {
    next('/login')
  } else if (to.path === '/login' && authStore.isLoggedIn) {
    next('/')
  } else {
    next()
  }
})

export default router
