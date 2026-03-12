<template>
  <div class="user-page">
    <el-card shadow="never" class="main-card">
      <!-- 工具栏 -->
      <div class="toolbar">
        <div class="toolbar-left">
          <el-input
            v-model="query.keyword"
            placeholder="搜索用户名 / 手机号"
            :prefix-icon="Search"
            clearable
            style="width: 260px"
            @keyup.enter="fetchList"
          />
          <el-button type="primary" :icon="Search" @click="fetchList">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </div>
        <div class="toolbar-right">
          <span class="total-tip">共 {{ total }} 位用户</span>
        </div>
      </div>

      <!-- 表格 -->
      <el-table
        :data="list"
        v-loading="loading"
        stripe
        class="mt-16"
        :header-cell-style="{ background: '#fafafa', color: '#666', fontWeight: '600' }"
      >
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column label="头像" width="70" align="center">
          <template #default="{ row }">
            <el-avatar :size="34" :src="row.avatar" style="background:#1677ff">
              <el-icon><User /></el-icon>
            </el-avatar>
          </template>
        </el-table-column>
        <el-table-column prop="nickname" label="昵称" width="120" />
        <el-table-column prop="phone" label="手机号" width="140" />
        <el-table-column label="性别" width="80" align="center">
          <template #default="{ row }">
            {{ genderLabel(row.gender) }}
          </template>
        </el-table-column>
        <el-table-column label="出生日期" width="130">
          <template #default="{ row }">
            {{ row.birthday || '—' }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="注册时间" width="200" :formatter="formatTime" />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="130" fixed="right" align="center">
          <template #default="{ row }">
            <el-button text type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button text type="danger"  size="small" @click="handleDelete(row)">删除</el-button>
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
          layout="total, sizes, prev, pager, next, jumper"
          background
          @change="fetchList"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, User } from '@element-plus/icons-vue'
import { getUserList, deleteUser } from '@/api/user'

// ── 性别显示 ──────────────────────────────────────
function genderLabel(val: any): string {
  if (val === 1 || val === '1' || val === '男') return '男'
  if (val === 2 || val === '2' || val === '女') return '女'
  return '—'
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
const list    = ref<any[]>([])
const total   = ref(0)

const query = reactive({ page: 1, pageSize: 10, keyword: '' })

async function fetchList() {
  loading.value = true
  try {
    const res: any = await getUserList(query)
    list.value  = res.data?.data  || []
    total.value = res.data?.total || 0
  } catch {
    // 拦截器统一处理
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.keyword  = ''
  query.page     = 1
  fetchList()
}

function handleEdit(row: any) {
  ElMessage.info(`编辑用户：${row.nickname || row.id}`)
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm(
    `确定删除用户「${row.nickname || row.id}」吗？此操作不可恢复。`,
    '删除确认',
    { type: 'warning', confirmButtonText: '删除', confirmButtonClass: 'el-button--danger' }
  )
  await deleteUser(row.id)
  ElMessage.success('删除成功')
  fetchList()
}

onMounted(fetchList)
</script>

<style scoped>
.user-page { }

.main-card { border-radius: 8px; }

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.toolbar-left  { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.toolbar-right { }
.total-tip     { font-size: 13px; color: #999; }

.mt-16     { margin-top: 16px; }
.pagination {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}
</style>
