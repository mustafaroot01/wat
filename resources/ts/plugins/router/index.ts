import { useAdminPermissions } from '@/composables/useAdminPermissions'
import type { App } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from './routes'

const ROUTE_PRIORITY = [
  { path: '/dashboard',      permission: 'dashboard'      },
  { path: '/orders',         permission: 'orders'         },
  { path: '/products',       permission: 'products'       },
  { path: '/categories',     permission: 'categories'     },
  { path: '/brands',         permission: 'brands'         },
  { path: '/discounts',      permission: 'discounts'      },
  { path: '/customers',      permission: 'customers'      },
  { path: '/notifications',  permission: 'notifications'  },
  { path: '/coupons',        permission: 'coupons'        },
  { path: '/banners',        permission: 'banners'        },
  { path: '/districts',      permission: 'districts'      },
  { path: '/store-settings', permission: 'store-settings' },
  { path: '/settings',       permission: 'settings'       },
  { path: '/admins',         permission: 'admins'         },
]

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
      // إذا كان التحويل لصفحة الداشبورد (تحويل بعد تسجيل الدخول)، ابحث عن أول صفحة متاحة
      if (to.path === '/dashboard') {
        for (const r of ROUTE_PRIORITY) {
          if (can(r.permission)) return next(r.path)
        }
        return next('/account-settings')
      }
      return next('/403')
    }
  }

  next()
})

export default function (app: App) {
  app.use(router)
}

export { router }
