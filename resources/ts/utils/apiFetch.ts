/**
 * Centralized fetch wrapper — attaches Bearer token and handles 401 globally.
 */
export async function apiFetch(url: string, options: RequestInit = {}): Promise<Response> {
  const token = localStorage.getItem('accessToken')

  const headers = new Headers(options.headers)

  if (!headers.has('Accept'))
    headers.set('Accept', 'application/json')

  if (token)
    headers.set('Authorization', `Bearer ${token}`)

  // Do NOT set Content-Type for FormData; browser sets it with the boundary automatically
  if (!(options.body instanceof FormData) && !headers.has('Content-Type'))
    headers.set('Content-Type', 'application/json')

  const response = await fetch(url, { ...options, headers })

  if (response.status === 401) {
    localStorage.removeItem('accessToken')
    localStorage.removeItem('userData')
    window.location.href = '/login'
  }

  return response
}
