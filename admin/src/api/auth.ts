import request from '@/utils/request'

export interface LoginParams {
  email: string
  password: string
}

export interface LoginResult {
  token: string
  user: {
    id: number
    name: string
    email: string
    role: string
    avatar?: string
  }
}

/** 管理员登录 */
export const login = (data: LoginParams) =>
  request.post<any, LoginResult>('/admin/login', data)

/** 获取当前用户信息 */
export const getProfile = () => request.get('/admin/profile')

/** 退出登录 */
export const logout = () => request.post('/admin/logout')
