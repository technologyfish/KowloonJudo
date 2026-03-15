<template>
  <div class="reg-page">
    <el-card  shadow="never">
      <!-- 搜索工具栏 -->
      <div class="toolbar">
        <div class="toolbar-left">
          <el-input
            v-model="query.keyword"
            placeholder="搜索姓名/手机号/战队"
            :prefix-icon="Search"
            clearable
            style="width:220px"
            @keyup.enter="fetchList"
          />
          <el-select v-model="query.pay_status" placeholder="支付状态" clearable style="width:140px">
            <el-option label="待支付" value="pending" />
            <el-option label="已支付" value="paid" />
            <el-option label="已取消" value="cancelled" />
            <el-option label="退款中" value="refund_pending" />
            <el-option label="已退款" value="refunded" />
          </el-select>
          <el-select v-model="query.confirm_status" placeholder="审核状态" clearable style="width:120px">
            <el-option label="未通过" value="pending" />
            <el-option label="已通过" value="confirmed" />
          </el-select>
          <el-select v-model="query.age_group" placeholder="年龄组别" clearable style="width:140px">
            <el-option v-for="a in AGE_GROUPS" :key="a" :label="a" :value="a" />
          </el-select>
          <el-button type="primary" :icon="Search" @click="fetchList">搜索</el-button>
        </div>
        <div class="toolbar-right">
          <el-dropdown @command="handleExportCommand">
            <el-button :icon="Download">
              导出 <el-icon class="el-icon--right"><ArrowDown /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="confirmed">仅导出已确认</el-dropdown-item>
                <el-dropdown-item command="all">导出全部</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>

      <!-- 数据表格 -->
      <el-table
        :data="list"
        v-loading="loading"
        class="mt-16"
        stripe
        border
      >
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="order_no" label="订单号" width="140" />
        <el-table-column prop="package_label" label="套餐" width="160" />
        <el-table-column prop="amount" label="金额" width="80">
          <template #default="{ row }">
            <span style="color:#e74c3c;font-weight:bold">¥{{ row.amount }}</span>
          </template>
        </el-table-column>
        <el-table-column label="支付状态" width="100">
          <template #default="{ row }">
            <el-tag :type="payStatusType(row.pay_status)" size="small">
              {{ payStatusLabel(row.pay_status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="审核状态" width="100">
          <template #default="{ row }">
            <el-tag
              :type="row.confirm_status === 'confirmed' ? 'success' : 'danger'"
              size="small"
            >
              {{ row.confirm_status === 'confirmed' ? '已通过' : '未通过' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="name_cn" label="姓名（汉字）" width="110" />
        <el-table-column prop="name_pinyin" label="姓名（拼音）" width="130" />
        <el-table-column prop="gender" label="性别" width="70" />
        <el-table-column prop="birthday" label="出生日期" width="110" />
        <el-table-column prop="age_group" label="年龄组" width="110" />
        <el-table-column prop="belt_color" label="带色" width="80" />
        <el-table-column prop="weight_gi" label="体重（道服）" width="120" />
        <el-table-column prop="weight_nogi" label="体重（无道服）" width="130" />
        <el-table-column prop="team" label="战队" width="120" />
        <el-table-column prop="phone" label="手机号" width="130" />

        <el-table-column prop="created_at" label="报名时间" width="200" :formatter="formatTime" />
        <el-table-column label="操作" width="280" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <el-button
                type="primary"
                size="small"
                plain
                @click="handleEdit(row)"
              >
                修改
              </el-button>
              <el-button
                type="danger"
                size="small"
                plain
                @click="handleDelete(row)"
              >
                删除
              </el-button>
              <el-button
                v-if="row.pay_status === 'paid' && row.confirm_status !== 'confirmed'"
                type="success"
                size="small"
                plain
                @click="handleAudit(row)"
              >
                人工通过
              </el-button>
              <el-button
                v-if="row.pay_status === 'paid' && row.confirm_status === 'confirmed'"
                type="info"
                size="small"
                plain
                @click="handleCancelAudit(row)"
              >
                取消通过
              </el-button>
              <el-button
                v-if="row.pay_status === 'paid' || row.pay_status === 'refund_pending'"
                type="warning"
                size="small"
                plain
                @click="handleRefund(row)"
              >
                退款
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="query.page"
          v-model:page-size="query.pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next"
          @change="fetchList"
        />
      </div>
    </el-card>

    <!-- 审核预览对话框（只读） -->
    <el-dialog v-model="auditDialogVisible" title="审核报名信息" width="650px" destroy-on-close>
      <el-descriptions :column="2" border size="default">
        <el-descriptions-item label="订单号">{{ auditRow.order_no }}</el-descriptions-item>
        <el-descriptions-item label="套餐">{{ auditRow.package_label }}</el-descriptions-item>
        <el-descriptions-item label="金额">
          <span style="color:#e74c3c;font-weight:bold">¥{{ auditRow.amount }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="支付状态">
          <el-tag :type="payStatusType(auditRow.pay_status)" size="small">
            {{ payStatusLabel(auditRow.pay_status) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="审核状态">
          <el-tag :type="auditRow.confirm_status === 'confirmed' ? 'success' : 'danger'" size="small">
            {{ auditRow.confirm_status === 'confirmed' ? '已通过' : '未通过' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="姓名（汉字）">{{ auditRow.name_cn }}</el-descriptions-item>
        <el-descriptions-item label="姓名（拼音）">{{ auditRow.name_pinyin }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ auditRow.gender }}</el-descriptions-item>
        <el-descriptions-item label="出生日期">{{ auditRow.birthday }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ auditRow.phone }}</el-descriptions-item>
        <el-descriptions-item label="邮箱">{{ auditRow.email }}</el-descriptions-item>
        <el-descriptions-item label="身份证">{{ auditRow.id_card }}</el-descriptions-item>
        <el-descriptions-item label="国籍">{{ auditRow.nationality }}</el-descriptions-item>
        <el-descriptions-item label="年龄组别">{{ auditRow.age_group }}</el-descriptions-item>
        <el-descriptions-item label="带色">{{ auditRow.belt_color }}</el-descriptions-item>
        <el-descriptions-item label="体重（道服）">{{ auditRow.weight_gi || '—' }}</el-descriptions-item>
        <el-descriptions-item label="体重（无道服）">{{ auditRow.weight_nogi || '—' }}</el-descriptions-item>
        <el-descriptions-item label="战队">{{ auditRow.team }}</el-descriptions-item>


      </el-descriptions>
      <template #footer>
        <el-button @click="auditDialogVisible = false">关闭</el-button>
        <el-button type="success" :loading="auditLoading" @click="handleAuditConfirm">确认通过</el-button>
      </template>
    </el-dialog>

    <!-- 编辑对话框 -->
    <el-dialog v-model="editDialogVisible" title="修改报名信息" width="700px" destroy-on-close>
      <el-form :model="editForm" label-width="110px" v-loading="editLoading">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="姓名（汉字）">
              <el-input v-model="editForm.name_cn" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="姓名（拼音）">
              <el-input v-model="editForm.name_pinyin" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="性别">
              <el-select v-model="editForm.gender" style="width:100%" @change="onGenderChange">
                <el-option label="男" value="男" />
                <el-option label="女" value="女" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="出生日期">
              <el-date-picker v-model="editForm.birthday" type="date" value-format="YYYY-MM-DD" style="width:100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="手机号">
              <el-input v-model="editForm.phone" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="邮箱">
              <el-input v-model="editForm.email" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="身份证">
              <el-input v-model="editForm.id_card" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="国籍">
              <el-input v-model="editForm.nationality" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="年龄组别">
              <el-select v-model="editForm.age_group" style="width:100%" @change="onAgeGroupChange">
                <el-option v-for="a in AGE_GROUPS" :key="a" :label="a" :value="a" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="带色">
              <el-select v-model="editForm.belt_color" style="width:100%">
                <el-option v-for="b in editBeltOptions" :key="b" :label="b" :value="b" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="体重（道服）">
              <el-select v-model="editForm.weight_gi" style="width:100%" clearable>
                <el-option v-for="w in editGiWeightOptions" :key="w" :label="w" :value="w" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="体重（无道服）">
              <el-select v-model="editForm.weight_nogi" style="width:100%" clearable>
                <el-option v-for="w in editNogiWeightOptions" :key="w" :label="w" :value="w" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="战队">
              <el-input v-model="editForm.team" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="支付状态">
              <el-select v-model="editForm.pay_status" style="width:100%">
                <el-option label="待支付" value="pending" />
                <el-option label="已支付" value="paid" />
                <el-option label="已取消" value="cancelled" />
                <el-option label="退款中" value="refund_pending" />
                <el-option label="已退款" value="refunded" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="审核状态">
              <el-select v-model="editForm.confirm_status" style="width:100%">
                <el-option label="未通过" value="pending" />
                <el-option label="已通过" value="confirmed" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="editLoading" @click="handleEditSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Download, ArrowDown } from '@element-plus/icons-vue'
import {
  getRegistrationList,
  exportRegistrations,
  confirmRegistration,
  refundRegistration,
  updateRegistration,
  deleteRegistration,
} from '@/api/competition'

const AGE_GROUPS = [
  '儿童组1(4-6岁)', '儿童组2(7-9岁)', '儿童组3(10-12岁)',
  '少年组(13-15岁)', '青年组(16-17岁)',
  '成人(18岁以上)', '大师1(30岁以上)', '大师2(40岁以上)',
]

// ── 体重 / 带色数据（与小程序一致）──────────────────
const CHILDREN_GROUPS = ['儿童组1(4-6岁)', '儿童组2(7-9岁)', '儿童组3(10-12岁)', '少年组(13-15岁)']
const YOUTH_GROUP = '青年组(16-17岁)'
const ADULT_GROUPS_LIST = ['成人(18岁以上)', '大师1(30岁以上)', '大师2(40岁以上)']
const CHILDREN_BELTS = ['白带', '灰带', '黄带', '橙带', '绿带']
const ADULT_BELTS = ['白带', '蓝带', '紫带', '棕带', '黑带']

const GI_WEIGHTS: Record<string, string[]> = {
  '儿童组1(4-6岁)': ['-17.90kg', '-19.90kg', '-22.00kg', '-25.00kg', '-28.00kg', '-31.20kg', '-34.20kg', '+34.20kg以上'],
  '儿童组2(7-9岁)': ['-21.00kg', '-24.00kg', '-27.00kg', '-30.20kg', '-33.20kg', '-36.20kg', '-39.30kg', '-42.30kg', '+42.30kg以上'],
  '儿童组3(10-12岁)': ['-30.20kg', '-33.20kg', '-36.20kg', '-39.30kg', '-42.30kg', '-45.30kg', '-48.30kg', '-51.30kg', '+51.30kg以上'],
  '少年组(13-15岁)': ['-40.30kg', '-44.30kg', '-48.30kg', '-52.50kg', '-56.50kg', '-60.50kg', '-65.00kg', '-69.00kg', '+69.00kg以上'],
  '青年组(16-17岁)_女': ['-44.30kg', '-48.30kg', '-52.50kg', '-56.50kg', '-60.50kg', '-65.50kg', '-69.00kg', '+69.00kg以上'],
  '青年组(16-17岁)_男': ['-53.50kg', '-58.50kg', '-64.00kg', '-69.00kg', '-74.00kg', '-79.30kg', '-84.30kg', '-89.30kg', '+89.30kg以上'],
  '成人_女': ['最轻量级 ~48.50kg以下', '超羽量级 -53.50kg以下', '羽量级 -58.50kg以下', '轻量级 -64.00kg以下', '中量级 -69.00kg以下', '次重量级 -74.00kg以下', '重量级 -79.30kg以下', '超重量级 -79.30kg以上'],
  '成人_男': ['最轻量级 -57.50kg以下', '超羽量级 -64.00kg以下', '羽量级 -70.00kg以下', '轻量级 -76.00kg以下', '中量级 -82.30kg以下', '次重量级 -88.30kg以下', '重量级 -94.30kg以下', '超重量级 -100.50kg以下', '最重量级 -100.50kg以上'],
}

const NOGI_WEIGHTS: Record<string, string[]> = {
  '儿童组1(4-6岁)': ['-16.60kg', '-18.60kg', '-20.70kg', '-23.70kg', '-26.70kg', '-29.60kg', '-32.90kg', '+32.90kg以上'],
  '儿童组2(7-9岁)': ['-19.70kg', '-22.70kg', '-25.70kg', '-28.90kg', '-31.90kg', '-34.90kg', '-38.00kg', '-41.00kg', '+41.00kg以上'],
  '儿童组3(10-12岁)': ['-28.90kg', '-31.90kg', '-34.90kg', '-38.00kg', '-41.00kg', '-44.00kg', '-47.00kg', '-50.00kg', '+50.00kg以上'],
  '少年组(13-15岁)': ['-39.00kg', '-43.00kg', '-47.00kg', '-51.20kg', '-55.20kg', '-59.30kg', '-63.70kg', '-67.70kg', '+67.70kg以上'],
  '青年组(16-17岁)_女': ['-42.50kg', '-46.50kg', '-50.50kg', '-54.50kg', '-58.50kg', '-62.50kg', '-66.50kg', '+66.50kg以上'],
  '青年组(16-17岁)_男': ['-51.50kg', '-56.50kg', '-61.50kg', '-66.50kg', '-71.50kg', '-76.50kg', '-81.50kg', '-86.50kg', '+86.50kg以上'],
  '成人_女': ['-46.50kg', '-51.50kg', '-56.50kg', '-61.50kg', '-66.50kg', '-71.50kg', '-76.50kg', '+76.50kg以上'],
  '成人_男': ['-55.50kg', '-61.50kg', '-67.50kg', '-73.50kg', '-79.50kg', '-85.50kg', '-91.50kg', '-97.50kg', '+97.50kg以上'],
}

function lookupWeights(table: Record<string, string[]>, ageGroup: string, gender: string): string[] {
  if (!ageGroup) return []
  if (CHILDREN_GROUPS.includes(ageGroup)) return table[ageGroup] || []
  if (ageGroup === YOUTH_GROUP) return table[`${YOUTH_GROUP}_${gender || '男'}`] || []
  if (ADULT_GROUPS_LIST.includes(ageGroup)) return table[`成人_${gender || '男'}`] || []
  return []
}

// 编辑对话框的联动计算
const editBeltOptions = computed(() => {
  const ag = editForm.age_group
  if (!ag) return [...CHILDREN_BELTS, ...ADULT_BELTS]
  if (CHILDREN_GROUPS.includes(ag) || ag === YOUTH_GROUP) return CHILDREN_BELTS
  return ADULT_BELTS
})

const editGiWeightOptions = computed(() => lookupWeights(GI_WEIGHTS, editForm.age_group, editForm.gender))
const editNogiWeightOptions = computed(() => lookupWeights(NOGI_WEIGHTS, editForm.age_group, editForm.gender))

function onAgeGroupChange() {
  // 年龄组别变更时，重置带色和体重（因为选项列表变了）
  if (!editBeltOptions.value.includes(editForm.belt_color)) {
    editForm.belt_color = ''
  }
  if (!editGiWeightOptions.value.includes(editForm.weight_gi)) {
    editForm.weight_gi = ''
  }
  if (!editNogiWeightOptions.value.includes(editForm.weight_nogi)) {
    editForm.weight_nogi = ''
  }
}

function onGenderChange() {
  // 性别变更时，重置体重（因为选项列表变了）
  if (!editGiWeightOptions.value.includes(editForm.weight_gi)) {
    editForm.weight_gi = ''
  }
  if (!editNogiWeightOptions.value.includes(editForm.weight_nogi)) {
    editForm.weight_nogi = ''
  }
}

// ── 时间格式化 ─────────────────────────────────────
function formatTime(_row: any, _col: any, val: string) {
  if (!val) return ''
  const d = new Date(val)
  if (isNaN(d.getTime())) return val
  const p = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
}

const loading = ref(false)
const list = ref<any[]>([])
const total = ref(0)

const query = reactive({
  page: 1,
  pageSize: 20,
  keyword: '',
  pay_status: '',
  confirm_status: '',
  age_group: ''
})

async function fetchList() {
  loading.value = true
  try {
    const res: any = await getRegistrationList(query)
    list.value = res.data?.data || []
    total.value = res.data?.total || 0
  } finally {
    loading.value = false
  }
}
onMounted(fetchList)

// ── 状态 ─────────────────────────────────────────
function payStatusLabel(s: string) {
  return { pending: '待支付', paid: '已支付', cancelled: '已取消', refund_pending: '退款中', refunded: '已退款' }[s] || s
}
function payStatusType(s: string) {
  return { pending: 'warning', paid: 'success', cancelled: 'info', refund_pending: 'danger', refunded: 'danger' }[s] || ''
}

// ── 审核（人工通过）─────────────────────────────────
const auditDialogVisible = ref(false)
const auditLoading = ref(false)
const auditRow = ref<any>({})

function handleAudit(row: any) {
  auditRow.value = { ...row }
  auditDialogVisible.value = true
}

async function handleAuditConfirm() {
  auditLoading.value = true
  try {
    await confirmRegistration(auditRow.value.id)
    ElMessage.success('审核已通过')
    auditDialogVisible.value = false
    fetchList()
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '操作失败')
  } finally {
    auditLoading.value = false
  }
}

async function handleCancelAudit(row: any) {
  try {
    await ElMessageBox.confirm(
      `确定取消「${row.name_cn || row.name_pinyin}」的审核通过状态？`,
      '取消通过',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
    await updateRegistration(row.id, { confirm_status: 'pending' })
    ElMessage.success('已取消通过')
    fetchList()
  } catch {
    // cancelled
  }
}

// ── 退款 ─────────────────────────────────────────
async function handleRefund(row: any) {
  try {
    await ElMessageBox.confirm(
      `确定对选手「${row.name_cn || row.name_pinyin}」的订单 #${row.id}（¥${row.amount}）进行退款？此操作不可撤销！`,
      '退款确认',
      {
        confirmButtonText: '确定退款',
        cancelButtonText: '取消',
        type: 'warning',
      }
    )
    await refundRegistration(row.id)
    ElMessage.success('退款成功')
    fetchList()
  } catch {
    // cancelled
  }
}

// ── 编辑 ─────────────────────────────────────────
const editDialogVisible = ref(false)
const editLoading = ref(false)
const editingId = ref<number>(0)
const editForm = reactive({
  name_cn: '',
  name_pinyin: '',
  gender: '',
  birthday: '',
  phone: '',
  email: '',
  id_card: '',
  nationality: '',
  age_group: '',
  belt_color: '',
  weight_gi: '',
  weight_nogi: '',
  team: '',
  pay_status: '',
  confirm_status: '',
})

function handleEdit(row: any) {
  editingId.value = row.id
  editForm.name_cn = row.name_cn || ''
  editForm.name_pinyin = row.name_pinyin || ''
  editForm.gender = row.gender || ''
  editForm.birthday = row.birthday || ''
  editForm.phone = row.phone || ''
  editForm.email = row.email || ''
  editForm.id_card = row.id_card || ''
  editForm.nationality = row.nationality || ''
  editForm.age_group = row.age_group || ''
  editForm.belt_color = row.belt_color || ''
  editForm.weight_gi = row.weight_gi || ''
  editForm.weight_nogi = row.weight_nogi || ''
  editForm.team = row.team || ''
  editForm.pay_status = row.pay_status || 'pending'
  editForm.confirm_status = row.confirm_status || 'pending'
  editDialogVisible.value = true
}

async function handleEditSubmit() {
  editLoading.value = true
  try {
    await updateRegistration(editingId.value, { ...editForm })
    ElMessage.success('修改成功')
    editDialogVisible.value = false
    fetchList()
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '修改失败')
  } finally {
    editLoading.value = false
  }
}

// ── 删除 ─────────────────────────────────────────
async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(
      `确定删除选手「${row.name_cn || row.name_pinyin || row.id}」的报名记录？此操作不可撤销！`,
      '删除确认',
      {
        confirmButtonText: '确定删除',
        cancelButtonText: '取消',
        type: 'warning',
      }
    )
    await deleteRegistration(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (e: any) {
    if (e !== 'cancel' && e?.response?.data?.message) {
      ElMessage.error(e.response.data.message)
    }
  }
}

// ── 导出 ─────────────────────────────────────────
async function handleExportCommand(command: string) {
  try {
    const isAll = command === 'all'
    const res: any = await exportRegistrations(isAll)
    const url = URL.createObjectURL(new Blob([res], { type: 'text/csv;charset=utf-8' }))
    const a = document.createElement('a')
    a.href = url
    a.download = `registrations_${isAll ? 'all' : 'confirmed'}_${Date.now()}.csv`
    a.click()
    URL.revokeObjectURL(url)
    ElMessage.success('导出成功')
  } catch {
    ElMessage.error('导出失败')
  }
}
</script>

<style scoped>
.reg-page {}
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 4px;
}
.toolbar-left { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.toolbar-right { display: flex; gap: 10px; align-items: center; }
.mt-16 { margin-top: 16px; }
.pagination { margin-top: 16px; display: flex; justify-content: flex-end; }
.action-btns { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
</style>
