import api from './api'

export const debugApi = {
  /**
   * Verify JWT token validity on backend
   */
  async verifyToken() {
    try {
      const response = await api.get('/debug/token-verify')
      console.log('[DEBUG] Token verification result:', response.data)
      return response.data
    } catch (error: any) {
      console.error('[DEBUG] Token verification failed:', error.response?.data)
      throw error
    }
  },

  /**
   * Get current token from localStorage
   */
  getStoredToken() {
    const token = localStorage.getItem('admin_access_token')
    console.log('[DEBUG] Stored token:', token)
    return token
  },

  /**
   * Log all localStorage keys
   */
  logStorage() {
    console.log('[DEBUG] localStorage contents:')
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i)
      const value = localStorage.getItem(key!)
      console.log(`  ${key}:`, value?.substring(0, 50) + '...')
    }
  },
}

// Expose to window for console access
;(window as any).debugApi = debugApi
