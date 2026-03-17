<template>
  <div class="rules-page">
    <!-- 工具栏 -->
    <el-card class="toolbar-card" shadow="never">
      <div class="toolbar">
        <span class="page-desc">管理小程序首页顶部展示的比赛规则（富文本）</span>
        <el-button type="primary" :icon="Plus" @click="openCreate">新建规则</el-button>
      </div>
    </el-card>

    <!-- 规则列表 -->
    <el-card class="table-card" shadow="never">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="title" label="标题" min-width="200" />
        <el-table-column prop="created_at" label="创建时间" width="200" :formatter="formatTime" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button text type="primary" size="small" @click="openEdit(row)">编辑</el-button>
            <el-button text type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 创建/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="editId ? '编辑比赛规则' : '新建比赛规则'"
      width="900px"
      top="6vh"
      destroy-on-close
      @closed="onDialogClosed"
    >
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="90px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入规则标题" />
        </el-form-item>

        <el-form-item label="简介" prop="summary">
          <el-input
            v-model="form.summary"
            type="textarea"
            :rows="2"
            placeholder="一句话简介（可选）"
          />
        </el-form-item>

        <el-form-item label="创建时间" prop="created_at">
          <el-date-picker
            v-model="form.created_at"
            type="date"
            value-format="YYYY-MM-DD"
            placeholder="默认为今日"
            style="width: 200px"
          />
        </el-form-item>

        <el-form-item label="规则内容" prop="content">
          <div class="editor-wrap">
            <Toolbar
              :editor="editorRef"
              :default-config="toolbarConfig"
              :mode="'default'"
              style="border-bottom: 1px solid #ccc"
            />
            <Editor
              v-model="form.content"
              :default-config="editorConfig"
              :mode="'default'"
              style="height: 400px; overflow-y: hidden"
              @onCreated="handleCreated"
            />
          </div>
          <div v-if="contentError" class="el-form-item__error">请输入规则内容</div>
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
import { ref, reactive, onMounted, shallowRef, onBeforeUnmount } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import '@wangeditor/editor/dist/css/style.css'
import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
import {
  getRuleList, createRule, updateRule, deleteRule,
  type CompetitionRule
} from '@/api/competition'

// ── WangEditor 实例 ─────────────────────────────────────
const editorRef = shallowRef()

const toolbarConfig = {}
const editorConfig = {
  placeholder: '请在此输入比赛规则内容...',
}

function handleCreated(editor: any) {
  editorRef.value = editor
}

// 对话框关闭时销毁编辑器实例
function onDialogClosed() {
  if (editorRef.value) {
    editorRef.value.destroy()
    editorRef.value = null
  }
}

onBeforeUnmount(() => {
  if (editorRef.value) {
    editorRef.value.destroy()
  }
})

// ── 时间格式化 ─────────────────────────────────────
function formatTime(_row: any, _col: any, val: string) {
  if (!val) return ''
  const d = new Date(val)
  if (isNaN(d.getTime())) return val
  const p = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
}

// ── 列表 ──────────────────────────────────────────
const list = ref<CompetitionRule[]>([])
const loading = ref(false)

async function fetchList() {
  loading.value = true
  try {
    const res: any = await getRuleList()
    list.value = res.data || []
  } finally {
    loading.value = false
  }
}
onMounted(fetchList)

// ── 表单对话框 ────────────────────────────────────
const dialogVisible = ref(false)
const saving = ref(false)
const editId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const contentError = ref(false)

const defaultDate = () => new Date().toISOString().slice(0, 10)

const form = reactive({
  title: '',
  summary: '',
  content: '',
  created_at: defaultDate()
})

const formRules: FormRules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }]
}

function resetForm() {
  form.title = ''
  form.summary = ''
  form.content = ''
  form.created_at = defaultDate()
  contentError.value = false
  editId.value = null
}

function openCreate() {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: CompetitionRule) {
  resetForm()
  editId.value = row.id!
  form.title = row.title
  form.summary = (row as any).summary || ''
  form.content = row.content
  form.created_at = (row as any).created_at?.slice(0, 10) || defaultDate()
  dialogVisible.value = true
}

async function handleSave() {
  await formRef.value?.validate()
  if (!form.content.trim() || form.content === '<p><br></p>') {
    contentError.value = true
    return
  }
  saving.value = true
  try {
    if (editId.value) {
      await updateRule(editId.value, form)
      ElMessage.success('更新成功')
    } else {
      await createRule(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row: CompetitionRule) {
  await ElMessageBox.confirm(`确定删除「${row.title}」吗？`, '提示', { type: 'warning' })
  await deleteRule(row.id!)
  ElMessage.success('删除成功')
  fetchList()
}
</script>

<style scoped>
.rules-page { display: flex; flex-direction: column; gap: 16px; }

.toolbar-card :deep(.el-card__body) { padding: 14px 20px; }

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.page-desc { font-size: 13px; color: #999; }

.table-card { flex: 1; }

/* ── WangEditor 容器 ── */
.editor-wrap {
  width: 100%;
  border: 1px solid #ccc;
  border-radius: 4px;
  overflow: hidden;
  z-index: 100;
}
</style>
