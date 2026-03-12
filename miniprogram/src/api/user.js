import { get, post } from '@/utils/request'

/**
 * 微信登录
 * @param {string} code wx.login 返回的 code
 */
export const wxLogin = (code) => post('/auth/wx-login', { code })

/**
 * 获取用户信息
 */
export const getUserInfo = () => get('/user/info')

/**
 * 更新用户信息
 */
export const updateUserInfo = (data) => post('/user/info', data)
