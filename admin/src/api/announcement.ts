import request from '@/utils/request'

export interface Announcement {
  id?: number
  title: string
  content: string
  status: number   // 1显示 0隐藏
  sort: number
  created_at?: string
  updated_at?: string
}

/** 获取公告列表 */
export const getAnnouncementList = (params?: Record<string, unknown>) =>
  request.get('/admin/announcements', { params })

/** 获取公告详情 */
export const getAnnouncementDetail = (id: number) =>
  request.get(`/admin/announcements/${id}`)

/** 新建公告 */
export const createAnnouncement = (data: Partial<Announcement>) =>
  request.post('/admin/announcements', data)

/** 更新公告 */
export const updateAnnouncement = (id: number, data: Partial<Announcement>) =>
  request.put(`/admin/announcements/${id}`, data)

/** 删除公告 */
export const deleteAnnouncement = (id: number) =>
  request.delete(`/admin/announcements/${id}`)
