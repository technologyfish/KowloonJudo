import axios from 'axios'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const request = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  timeout: 15000
})

// 请求拦截器 — 自动注入 Token，上传请求自动延长超时
request.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    // 上传文件时延长超时到 60 秒
    if (config.data instanceof FormData) {
      config.timeout = 60000
    }
    return config
  },
  (error) => Promise.reject(error)
)

// 响应拦截器
request.interceptors.response.use(
  (response) => {
    // Blob 类型响应（如 CSV 导出）直接返回原始数据，不做 JSON 解析
    if (response.config.responseType === 'blob') {
      return response.data
    }
    const { data } = response
    if (data.code !== undefined && data.code !== 0 && data.code !== 200) {
      ElMessage.error(data.message || '请求失败')
      return Promise.reject(new Error(data.message))
    }
    return data
  },
  (error) => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout()
      router.push('/login')
      ElMessage.error('登录已过期，请重新登录')
    } else if (error.response?.config?.responseType === 'blob') {
      // Blob 请求失败时，尝试读取错误信息
      const blob = error.response?.data
      if (blob instanceof Blob) {
        blob.text().then((text: string) => {
          try {
            const json = JSON.parse(text)
            ElMessage.error(json.message || '导出失败')
          } catch {
            ElMessage.error('导出失败')
          }
        })
      } else {
        ElMessage.error('导出失败')
      }
    } else {
      ElMessage.error(error.response?.data?.message || '网络错误')
    }
    return Promise.reject(error)
  }
)

export default request
