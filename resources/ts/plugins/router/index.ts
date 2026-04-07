import type { App } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from './routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.VITE_BASE_URL || '/'),
  routes,
})

// === Navigation Guards (Route Protection) === //
router.beforeEach((to, from, next) => {
  const isLoggedIn = !!localStorage.getItem('accessToken')

  // If the user is NOT logged in and trying to access a restricted page
  if (to.path !== '/login' && to.path !== '/register' && !isLoggedIn) {
    return next('/login')
  }

  // If the user IS logged in and trying to access login/register
  if ((to.path === '/login' || to.path === '/register') && isLoggedIn) {
    return next('/')
  }

  next()
})

export default function (app: App) {
  app.use(router)
}

export { router }
