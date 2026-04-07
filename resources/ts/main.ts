import { createApp } from 'vue'

import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'

// Styles
import '@core-scss/template/index.scss'
import '@layouts/styles/index.scss'

// Create vue app
const app = createApp(App)

// === Global Fetch Interceptor === //
const originalFetch = window.fetch
window.fetch = async (...args) => {
  let [resource, config] = args
  const url = typeof resource === 'string' ? resource : (resource instanceof Request ? resource.url : '')

  // Only intercept /api/ requests
  if (url.includes('/api/')) {
    config = config || {}
    config.headers = config.headers ? new Headers(config.headers) : new Headers()

    // Append Accept header to prevent HTML redirects
    if (!config.headers.has('Accept')) {
      config.headers.append('Accept', 'application/json')
    }

    // Append Auth Token dynamically
    const token = localStorage.getItem('accessToken')
    if (token && !config.headers.has('Authorization')) {
      config.headers.append('Authorization', `Bearer ${token}`)
    }

    // Reconstruct Request object with new headers if needed
    if (resource instanceof Request) {
      resource = new Request(resource, config)
      return originalFetch(resource).then(res => handleAuthError(res))
    }
  }

  return originalFetch(resource, config).then(res => handleAuthError(res))
}

// Redirect to login if token is expired/invalid (401)
const handleAuthError = (res: Response) => {
  if (res.status === 401) {
    localStorage.removeItem('accessToken')
    localStorage.removeItem('userData')
    window.location.href = '/login'
  }
  return res
}

// Register plugins
registerPlugins(app)

// Mount vue app
app.mount('#app')
