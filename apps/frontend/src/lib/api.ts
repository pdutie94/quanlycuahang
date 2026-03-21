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
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
    logger.debug(`[API] ${config.method?.toUpperCase()} ${config.url} [Token: ${token.substring(0, 20)}...${token.substring(token.length - 10)}]`)
  } else {
    logger.warn(`[API] ${config.method?.toUpperCase()} ${config.url} [NO TOKEN]`)
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
    const token = localStorage.getItem(TOKEN_KEY)

    logger.error(`[API] ${method} ${path}: ${status}`, {
      status,
      isAuthEndpoint,
      message: error?.response?.data?.message || error?.message,
      tokenPresent: Boolean(token),
      tokenLength: token?.length,
      fullResponse: error?.response?.data,
    })

    // Only auto-redirect on 401 for non-auth endpoints
    // Auth endpoints should handle their own 401 without redirect
    if (status === 401 && !isAuthEndpoint && window.location.pathname !== '/login') {
      logger.warn(`[API] Unauthorized on ${path}, will redirect to login in 10s (check console for errors)`)
      // Delay redirect to let error message and logs show on screen
      setTimeout(() => {
        localStorage.removeItem(TOKEN_KEY)
        window.location.href = '/login'
      }, 10000)
    }

    return Promise.reject(error)
  },
)

export { TOKEN_KEY }
export default api
