import axios from 'axios'
import { logger } from './logger'

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
  const token = localStorage.getItem(TOKEN_KEY)?.trim()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
    logger.debug(`[API] ${config.method?.toUpperCase()} ${config.url}`)
  }
  return config
})

api.interceptors.response.use(
  (response) => {
    logger.debug(`[API] ${response.status} ${response.config.url}`)
    return response
  },
  (error) => {
    const status = error?.response?.status
    const path = error?.config?.url || ''
    const isAuthEndpoint = path.includes('/auth/')
    const method = error?.config?.method?.toUpperCase() || 'UNKNOWN'
    logger.error(`[API] ${method} ${path}: ${status}`, {
      message: error?.response?.data?.message || error?.message,
    })

    // Only auto-redirect on 401 for non-auth endpoints
    if (status === 401 && !isAuthEndpoint && window.location.pathname !== '/login') {
      setTimeout(() => {
        localStorage.removeItem(TOKEN_KEY)
        window.location.href = '/login'
      }, 3000)
    }

    return Promise.reject(error)
  },
)

export { TOKEN_KEY }
export default api
