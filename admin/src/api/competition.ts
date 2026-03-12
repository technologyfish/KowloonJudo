import request from '@/utils/request'

export interface CompetitionRule {
  id?: number
  title: string
  content: string
  created_at?: string
}

/** 获取规则列表 */
export const getRuleList = () => request.get('/admin/competition/rules')

/** 获取规则详情 */
export const getRuleDetail = (id: number) => request.get(`/admin/competition/rules/${id}`)

/** 创建规则 */
export const createRule = (data: CompetitionRule) =>
  request.post('/admin/competition/rules', data)

/** 更新规则 */
export const updateRule = (id: number, data: CompetitionRule) =>
  request.put(`/admin/competition/rules/${id}`, data)

/** 删除规则 */
export const deleteRule = (id: number) =>
  request.delete(`/admin/competition/rules/${id}`)

/** 获取报名列表 */
export const getRegistrationList = (params?: any) =>
  request.get('/admin/competition/registrations', { params })

/** 获取报名详情 */
export const getRegistrationDetail = (id: number) =>
  request.get(`/admin/competition/registrations/${id}`)

/** 导出报名数据 CSV（默认仅已确认，all=1导出全部） */
export const exportRegistrations = (all = false) =>
  request.get('/admin/competition/registrations/export', {
    params: all ? { all: 1 } : {},
    responseType: 'blob',
  })

/** 确认报名 */
export const confirmRegistration = (id: number) =>
  request.put(`/admin/competition/registrations/${id}/confirm`)

/** 批量确认报名 */
export const batchConfirmRegistrations = (ids: number[]) =>
  request.put('/admin/competition/registrations/batch-confirm', { ids })

/** 退款 */
export const refundRegistration = (id: number) =>
  request.put(`/admin/competition/registrations/${id}/refund`)

/** 修改报名记录 */
export const updateRegistration = (id: number, data: any) =>
  request.put(`/admin/competition/registrations/${id}`, data)

/** 删除报名记录 */
export const deleteRegistration = (id: number) =>
  request.delete(`/admin/competition/registrations/${id}`)

/** 获取费用设置 */
export const getFeeSettings = () =>
  request.get('/admin/settings/fees')

/** 更新费用设置 */
export const updateFeeSettings = (data: { category_fee: number; open_weight_fee: number }) =>
  request.put('/admin/settings/fees', data)