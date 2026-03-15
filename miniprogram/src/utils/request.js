/**
 * 统一请求封装
 */
import { useUserStore } from '@/store/user'

export const BASE_URL = 'https://copade.net.cn/api'

// export const BASE_URL = 'http://127.0.0.1:8000/api'
/**
 * 发起请求
 * @param {object} options
 */
export const request = (options = {}) => {
  return new Promise((resolve, reject) => {
    const userStore = useUserStore()
    const token = userStore.token

    uni.request({
      url: BASE_URL + options.url,
      method: options.method || 'GET',
      data: options.data || {},
      header: {
        'Content-Type': 'application/json',
        Authorization: token ? `Bearer ${token}` : '',
        ...options.header
      },
      success(res) {
        if (res.statusCode === 401) {
          // token 过期，跳转登录
          userStore.logout()
          uni.reLaunch({ url: '/pages/login/index' })
          reject(new Error('未授权，请重新登录'))
          return
        }
        if (res.statusCode !== 200) {
          uni.showToast({
            title: res.data?.message || '请求失败',
            icon: 'none'
          })
          reject(res.data)
          return
        }
        resolve(res.data)
      },
      fail(err) {
        uni.showToast({ title: '网络连接失败', icon: 'none' })
        reject(err)
      }
    })
  })
}

export const get = (url, data = {}, options = {}) =>
  request({ url, method: 'GET', data, ...options })

export const post = (url, data = {}, options = {}) =>
  request({ url, method: 'POST', data, ...options })

export const put = (url, data = {}, options = {}) =>
  request({ url, method: 'PUT', data, ...options })

export const del = (url, data = {}, options = {}) =>
  request({ url, method: 'DELETE', data, ...options })
