import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authService } from '../services/authService'
import { TOKEN_KEY } from '../lib/api'
import { logger } from '../lib/logger'

type User = {
  id: number
  username: string
  full_name?: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string>(localStorage.getItem(TOKEN_KEY) || '')
  const initialized = ref(false)

  const isLoggedIn = computed(() => Boolean(token.value && user.value))

  function setToken(value: string): void {
    token.value = value
    localStorage.setItem(TOKEN_KEY, value)
    logger.info('[Auth] Token set', { hasToken: Boolean(value) })
  }

  function clearSession(): void {
    user.value = null
    token.value = ''
    localStorage.removeItem(TOKEN_KEY)
    logger.info('[Auth] Session cleared')
  }

  async function login(payload: { username: string; password: string }): Promise<void> {
    logger.info('[Auth] Attempting login', { username: payload.username })
    const result = await authService.login(payload)
    setToken(result.token)
    await fetchMe()
  }

  async function logout(): Promise<void> {
    logger.info('[Auth] Logging out')
    try {
      await authService.logout()
    } finally {
      clearSession()
    }
  }

  async function fetchMe(): Promise<void> {
    if (!token.value) {
      logger.info('[Auth] No token, skipping fetchMe')
      clearSession()
      return
    }

    try {
      logger.debug('[Auth] Fetching user info')
      user.value = await authService.getMe()
      logger.info('[Auth] User info loaded', { username: user.value.username })
    } catch (err) {
      logger.error('[Auth] fetchMe failed', err)
      clearSession()
      throw new Error('Token invalid or expired')
    }
  }

  async function init(): Promise<void> {
    if (initialized.value) return

    logger.info('[Auth] Initializing auth')

    if (!token.value) {
      logger.info('[Auth] No token found, skipping init')
      initialized.value = true
      return
    }

    try {
      await fetchMe()
      logger.info('[Auth] Init successful')
    } catch (err) {
      logger.warn('[Auth] Init failed, user logged out', err)
      // Token invalid: clearSession is already called in fetchMe.
    } finally {
      initialized.value = true
    }
  }

  return {
    user,
    token,
    isLoggedIn,
    initialized,
    login,
    logout,
    fetchMe,
    init,
  }
})
