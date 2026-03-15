import { get, post } from '@/utils/request'

/** 获取最新比赛规则 */
export const getLatestRule = () => get('/competition/rule')

/** 获取费用设置（组别费用 + 无差别费用） */
export const getFeeSettings = () => get('/settings/fees')

/** 提交报名表单，返回 order_id */
export const submitRegistration = (data) => post('/competition/register', data)

/** 创建微信支付订单，返回 prepay 参数 */
export const createPayOrder = (data) => post('/competition/pay/create', data)

/** 查询支付结果 */
export const queryPayResult = (orderId) => get('/competition/pay/query', { order_id: orderId })

/** 取消订单（仅待支付） */
export const cancelOrder = (data) => post('/competition/cancel', data)

/** 申请退款（仅已支付） */
export const requestRefund = (data) => post('/competition/refund', data)

/** 获取我的订单列表（支持状态筛选 + 分页） */
export const getMyOrders = (params = {}) => get('/competition/orders', params)

/** 获取单个订单详情 */
export const getOrderDetail = (id) => get('/competition/order-detail', { id })
