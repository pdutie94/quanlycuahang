import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authService } from '../services/authService'
import { TOKEN_KEY } from '../lib/api'

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
  }

  function clearSession(): void {
    user.value = null
    token.value = ''
    localStorage.removeItem(TOKEN_KEY)
  }

  async function login(payload: { username: string; password: string }): Promise<void> {
    const result = await authService.login(payload)
    setToken(result.token)
    await fetchMe()
  }

  async function logout(): Promise<void> {
    try {
      await authService.logout()
    } finally {
      clearSession()
    }
  }

  async function fetchMe(): Promise<void> {
    if (!token.value) {
      clearSession()
      return
    }

    try {
      user.value = await authService.getMe()
    } catch (err) {
      console.error('Auth verification failed:', err)
      clearSession()
      throw new Error('Token invalid or expired')
    }
  }

  async function init(): Promise<void> {
    if (initialized.value) return

    if (!token.value) {
      initialized.value = true
      return
    }

    try {
      await fetchMe()
    } catch (err) {
      console.error('Auth init failed, user logged out:', err)
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
