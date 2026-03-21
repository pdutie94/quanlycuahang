import api from '../lib/api'

type ApiSuccess<T> = {
  success: boolean
  data: T
  message: string
  error: unknown
}

type LoginResponse = {
  token: string
}

type MeResponse = {
  id: number
  username: string
  full_name?: string
}

export const authService = {
  async login(payload: { username: string; password: string }): Promise<LoginResponse> {
    const response = await api.post<ApiSuccess<LoginResponse>>('/auth/login', payload)
    return response.data.data
  },

  async logout(): Promise<void> {
    await api.post('/auth/logout')
  },

  async getMe(): Promise<MeResponse> {
    const response = await api.get<ApiSuccess<MeResponse>>('/auth/me')
    return response.data.data
  },
}
