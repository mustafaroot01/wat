import type { App } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from './routes'
import { useAdminPermissions } from '@/composables/useAdminPermissions'

const router = createRouter({
  history: createWebHistory(import.meta.env.VITE_BASE_URL || '/'),
  routes,
})

// === Navigation Guards (Route Protection) === //
router.beforeEach(async (to, from, next) => {
  let isLoggedIn = false
  try {
    isLoggedIn = !!localStorage.getItem('accessToken')
  } catch (e) {
    console.warn('localStorage is not available')
  }

  // Public routes that don't require auth
  const isPublic = to.path === '/login' || to.path === '/register' || to.path === '/403'
    || to.path.startsWith('/invoice')

  // If the user is NOT logged in and trying to access a restricted page
  if (!isPublic && !isLoggedIn) {
    return next({ path: '/login', query: { redirect: to.fullPath } })
  }

  // If the user IS logged in and trying to access login/register
  if ((to.path === '/login' || to.path === '/register') && isLoggedIn) {
    return next('/')
  }

  // Permission check for protected routes
  if (isLoggedIn && to.meta?.permission) {
    const { can, permissionsLoaded, fetchPermissions, adminInfo } = useAdminPermissions()

    // Load permissions if not yet loaded
    if (!permissionsLoaded.value) {
      await fetchPermissions()
    }

    // If still no data after fetch (API error), allow access to avoid full lockout
    if (!adminInfo.value) {
      return next()
    }

    if (!can(to.meta.permission as string)) {
      return next('/403')
    }
  }

  next()
})

export default function (app: App) {
  app.use(router)
}

export { router }
