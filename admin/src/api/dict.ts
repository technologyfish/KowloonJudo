import request from '@/utils/request'

// ── 字典类型 ──────────────────────────────────────

export interface DictType {
  id?: number
  code: string
  name: string
  status: number
  remark?: string
  items_count?: number
  created_at?: string
  updated_at?: string
}

/** 获取字典类型列表 */
export const getDictTypes = () => request.get('/admin/dict/types')

/** 创建字典类型 */
export const createDictType = (data: Partial<DictType>) =>
  request.post('/admin/dict/types', data)

/** 更新字典类型 */
export const updateDictType = (id: number, data: Partial<DictType>) =>
  request.put(`/admin/dict/types/${id}`, data)

/** 删除字典类型 */
export const deleteDictType = (id: number) =>
  request.delete(`/admin/dict/types/${id}`)

// ── 字典数据项 ────────────────────────────────────

export interface DictItem {
  id?: number
  type_code: string
  label: string
  value: string
  sort: number
  status: number
  remark?: string
  created_at?: string
  updated_at?: string
}

/** 获取字典数据项列表 */
export const getDictItems = (typeCode: string) =>
  request.get('/admin/dict/items', { params: { type_code: typeCode } })

/** 创建字典数据项 */
export const createDictItem = (data: Partial<DictItem>) =>
  request.post('/admin/dict/items', data)

/** 更新字典数据项 */
export const updateDictItem = (id: number, data: Partial<DictItem>) =>
  request.put(`/admin/dict/items/${id}`, data)

/** 删除字典数据项 */
export const deleteDictItem = (id: number) =>
  request.delete(`/admin/dict/items/${id}`)
