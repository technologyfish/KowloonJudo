<template>
  <div class="rules-page">
    <!-- 工具栏 -->
    <el-card class="toolbar-card">
      <div class="toolbar">
        <span class="page-desc">管理小程序首页顶部展示的比赛规则（富文本）</span>
        <el-button type="primary" :icon="Plus" @click="openCreate">新建规则</el-button>
      </div>
    </el-card>

    <!-- 规则列表 -->
    <el-card class="table-card">
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
      width="800px"
      top="6vh"
      destroy-on-close
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
          <!-- 富文本编辑器工具栏 -->
          <div class="editor-wrap">
            <div class="editor-toolbar">
              <el-button-group size="small">
                <el-button @click="execCmd('bold')"><b>B</b></el-button>
                <el-button @click="execCmd('italic')"><i>I</i></el-button>
                <el-button @click="execCmd('underline')"><u>U</u></el-button>
              </el-button-group>
              <el-button-group size="small" style="margin-left:8px">
                <el-button @click="execCmd('insertUnorderedList')">• 列表</el-button>
                <el-button @click="execCmd('insertOrderedList')">1. 列表</el-button>
              </el-button-group>
              <el-select
                v-model="fontSize"
                size="small"
                style="width:100px;margin-left:8px"
                @change="execCmd('fontSize', fontSize)"
              >
                <el-option v-for="s in [1,2,3,4,5,6,7]" :key="s" :label="`字号 ${s}`" :value="s" />
              </el-select>
            </div>
            <div
              ref="editorRef"
              class="editor-content"
              contenteditable="true"
              @input="onEditorInput"
              v-html="form.content"
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
import { ref, reactive, onMounted, nextTick } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getRuleList, createRule, updateRule, deleteRule,
  type CompetitionRule
} from '@/api/competition'

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
const editorRef = ref<HTMLDivElement>()
const fontSize = ref(3)
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
  nextTick(() => {
    if (editorRef.value) editorRef.value.innerHTML = ''
  })
}

function openEdit(row: CompetitionRule) {
  resetForm()
  editId.value = row.id!
  form.title = row.title
  form.summary = (row as any).summary || ''
  form.content = row.content
  form.created_at = (row as any).created_at?.slice(0, 10) || defaultDate()
  dialogVisible.value = true
  nextTick(() => {
    if (editorRef.value) editorRef.value.innerHTML = form.content
  })
}

function onEditorInput() {
  form.content = editorRef.value?.innerHTML || ''
  if (form.content) contentError.value = false
}

function execCmd(cmd: string, val?: any) {
  document.execCommand(cmd, false, val)
  form.content = editorRef.value?.innerHTML || ''
}

async function handleSave() {
  await formRef.value?.validate()
  if (!form.content.trim()) {
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

/* ── 富文本编辑器 ── */
.editor-wrap {
  width: 100%;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  overflow: hidden;
}
.editor-toolbar {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-bottom: 1px solid #ebeef5;
  background: #fafafa;
  flex-wrap: wrap;
  gap: 4px;
}
.editor-content {
  min-height: 240px;
  padding: 12px 14px;
  outline: none;
  font-size: 14px;
  line-height: 1.8;
  color: #333;
  overflow-y: auto;
}
.editor-content:empty::before {
  content: '请在此输入比赛规则内容...';
  color: #c0c4cc;
  pointer-events: none;
}
</style>
