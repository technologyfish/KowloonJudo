import request from '@/utils/request'

export interface DashboardStats {
  total_users: number
  today_users: number
  total_regs: number
  today_regs: number
  paid_count: number
  pending_count: number
  total_income: number
  trend: { date: string; count: number }[]
  age_distribution: { age_group: string; count: number }[]
}

/** 获取仪表盘统计数据 */
export const getDashboardStats = () =>
  request.get<any, { data: DashboardStats }>('/admin/dashboard/stats')
