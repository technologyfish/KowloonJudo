<template>
  <div class="announcement-page">

    <!-- 操作栏 -->
    <div class="toolbar">
      <el-button type="primary" :icon="Plus" @click="openCreate">新建公告</el-button>
    </div>

    <!-- 公告列表 -->
    <el-table :data="list" v-loading="loading" border stripe class="table">
      <el-table-column prop="id"      label="ID"   width="70"  align="center" />
      <el-table-column prop="title"   label="标题" min-width="180" />
      <el-table-column prop="content" label="内容" min-width="260" show-overflow-tooltip />
      <el-table-column prop="sort"    label="排序" width="80"  align="center" />
      <el-table-column label="状态"   width="90"  align="center">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
            {{ row.status === 1 ? '显示' : '隐藏' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="200" align="center" :formatter="formatTime" />
      <el-table-column label="操作" width="160" align="center">
        <template #default="{ row }">
          <el-button type="primary" link size="small" @click="openEdit(row)">编辑</el-button>
          <el-divider direction="vertical" />
          <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 新建/编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="form.id ? '编辑公告' : '新建公告'"
      width="600px"
      :close-on-click-modal="false"
      destroy-on-close
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="80px"
      >
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入公告标题" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="内容" prop="content">
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="5"
            placeholder="请输入公告内容（小程序展示用）"
          />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
          <span class="sort-tip">数值越大越靠前</span>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">显示</el-radio>
            <el-radio :value="0">隐藏</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import {
  getAnnouncementList,
  createAnnouncement,
  updateAnnouncement,
  deleteAnnouncement,
  type Announcement
} from '@/api/announcement'

// ── 时间格式化 ─────────────────────────────────────
function formatTime(_row: any, _col: any, val: string) {
  if (!val) return ''
  const d = new Date(val)
  if (isNaN(d.getTime())) return val
  const p = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
}

// ── 列表 ──────────────────────────────────────────────────────
const list    = ref<Announcement[]>([])
const loading = ref(false)

async function fetchList() {
  loading.value = true
  try {
    const res = await getAnnouncementList()
    list.value = res.data?.data || res.data || []
  } finally {
    loading.value = false
  }
}

onMounted(fetchList)

// ── 弹窗 & 表单 ───────────────────────────────────────────────
const dialogVisible = ref(false)
const saving        = ref(false)
const formRef       = ref<FormInstance>()

const defaultForm = (): Partial<Announcement> => ({
  id: undefined, title: '', content: '', status: 1, sort: 0
})

const form = reactive<Partial<Announcement>>(defaultForm())

const rules: FormRules = {
  title:   [{ required: true, message: '请输入标题', trigger: 'blur' }],
  content: [{ required: true, message: '请输入内容', trigger: 'blur' }],
}

function openCreate() {
  Object.assign(form, defaultForm())
  dialogVisible.value = true
}

function openEdit(row: Announcement) {
  Object.assign(form, { ...row })
  dialogVisible.value = true
}

async function handleSave() {
  await formRef.value?.validate()
  saving.value = true
  try {
    if (form.id) {
      await updateAnnouncement(form.id, form)
      ElMessage.success('更新成功')
    } else {
      await createAnnouncement(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    ElMessage.error(err?.response?.data?.message || '操作失败')
  } finally {
    saving.value = false
  }
}

async function handleDelete(row: Announcement) {
  await ElMessageBox.confirm(`确定删除公告「${row.title}」吗？`, '提示', {
    type: 'warning',
    confirmButtonText: '删除',
    confirmButtonClass: 'el-button--danger'
  })
  await deleteAnnouncement(row.id!)
  ElMessage.success('删除成功')
  fetchList()
}
</script>

<style scoped>
.announcement-page { }

.toolbar {
  margin-bottom: 16px;
  display: flex;
  justify-content: flex-end;
}

.table {
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
}

.sort-tip {
  margin-left: 10px;
  font-size: 12px;
  color: #999;
}
</style>
