import request from '@/utils/request'

export interface UserListParams {
  page?: number
  pageSize?: number
  keyword?: string
}

/** 获取用户列表 */
export const getUserList = (params: UserListParams) =>
  request.get('/admin/users', { params })

/** 获取用户详情 */
export const getUserDetail = (id: number) =>
  request.get(`/admin/users/${id}`)

/** 更新用户 */
export const updateUser = (id: number, data: any) =>
  request.put(`/admin/users/${id}`, data)

/** 删除用户 */
export const deleteUser = (id: number) =>
  request.delete(`/admin/users/${id}`)

/**
 * 上传图片（管理端通用）
 * @param file 文件对象
 * @param onProgress 上传进度回调
 */
export const uploadImage = (file: File, onProgress?: (percent: number) => void) => {
  const formData = new FormData()
  formData.append('file', file)
  return request.post('/admin/upload', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    timeout: 60000,
    onUploadProgress: (e: any) => {
      if (onProgress && e.total) {
        onProgress(Math.round((e.loaded / e.total) * 100))
      }
    }
  })
}
