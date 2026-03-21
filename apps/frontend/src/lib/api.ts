import axios from 'axios'

const TOKEN_KEY = 'admin_access_token'

const api = axios.create({
  baseURL: '/api',
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Don't auto-redirect on 401 — let auth store handle token refresh/logout
    // Only redirect if we're not on login page and status is 401 from non-auth endpoints
    const status = error?.response?.status
    const path = error?.config?.url || ''
    const isAuthEndpoint = path.includes('/auth/')

    if (status === 401 && !isAuthEndpoint && window.location.pathname !== '/login') {
      // Unauthorized on non-auth endpoint — clear token and redirect
      localStorage.removeItem(TOKEN_KEY)
      window.location.href = '/login'
    }

    return Promise.reject(error)
  },
)

export { TOKEN_KEY }
export default api
