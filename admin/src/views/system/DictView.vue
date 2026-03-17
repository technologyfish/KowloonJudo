<template>
  <div class="dict-page">

    <!-- ─── 字典类型列表 ─── -->
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>字典类型管理</span>
          <el-button type="primary" :icon="Plus" @click="openTypeDialog()">新增类型</el-button>
        </div>
      </template>

      <el-table :data="typeList" v-loading="typeLoading" stripe style="width: 100%">
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="code" label="字典编码" width="180" />
        <el-table-column prop="name" label="字典名称" width="160" />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="items_count" label="字典值数量" width="110" align="center" />
        <el-table-column prop="remark" label="备注" min-width="180" show-overflow-tooltip />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="220" align="center" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="openItemDrawer(row)">字典值</el-button>
            <el-button link type="primary" @click="openTypeDialog(row)">编辑</el-button>
            <el-popconfirm
              title="删除该类型将同时删除其下所有字典值，确定？"
              confirm-button-text="删除"
              cancel-button-text="取消"
              @confirm="handleDeleteType(row.id)"
            >
              <template #reference>
                <el-button link type="danger">删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- ─── 字典类型 新增/编辑 弹窗 ─── -->
    <el-dialog
      v-model="typeDialogVisible"
      :title="isEditType ? '编辑字典类型' : '新增字典类型'"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form ref="typeFormRef" :model="typeForm" :rules="typeRules" label-width="100px">
        <el-form-item label="字典编码" prop="code">
          <el-input
            v-model="typeForm.code"
            placeholder="如 competition_site"
            maxlength="50"
            :disabled="isEditType"
          />
          <div class="form-tip" v-if="!isEditType">唯一标识，创建后不可修改</div>
        </el-form-item>
        <el-form-item label="字典名称" prop="name">
          <el-input v-model="typeForm.name" placeholder="如 赛事站点" maxlength="100" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="typeForm.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="typeForm.remark" type="textarea" :rows="2" maxlength="255" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="typeDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="typeSaving" @click="handleSubmitType">确定</el-button>
      </template>
    </el-dialog>

    <!-- ─── 字典值管理 抽屉 ─── -->
    <el-drawer
      v-model="itemDrawerVisible"
      :title="`字典值管理 — ${currentType?.name || ''}`"
      size="600px"
      direction="rtl"
    >
      <div class="drawer-toolbar">
        <el-button type="primary" size="small" :icon="Plus" @click="openItemDialog()">新增字典值</el-button>
      </div>

      <el-table :data="itemList" v-loading="itemLoading" stripe size="small" style="width: 100%">
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="label" label="显示标签" min-width="140" />
        <el-table-column prop="value" label="存储值" min-width="140" />
        <el-table-column prop="sort" label="排序" width="70" align="center" />
        <el-table-column label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              size="small"
              @change="handleItemStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="130" align="center">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="openItemDialog(row)">编辑</el-button>
            <el-popconfirm
              title="确定删除该字典值？"
              confirm-button-text="删除"
              cancel-button-text="取消"
              @confirm="handleDeleteItem(row.id)"
            >
              <template #reference>
                <el-button link type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <!-- 字典值 新增/编辑 弹窗 -->
      <el-dialog
        v-model="itemDialogVisible"
        :title="isEditItem ? '编辑字典值' : '新增字典值'"
        width="460px"
        :close-on-click-modal="false"
        append-to-body
      >
        <el-form ref="itemFormRef" :model="itemForm" :rules="itemRules" label-width="90px">
          <el-form-item label="显示标签" prop="label">
            <el-input v-model="itemForm.label" placeholder="前端展示的文本" maxlength="100" />
          </el-form-item>
          <el-form-item label="存储值" prop="value">
            <el-input v-model="itemForm.value" placeholder="保存到数据库的值" maxlength="100" />
            <div class="form-tip">可与标签相同</div>
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input-number v-model="itemForm.sort" :min="0" :step="1" style="width: 160px" />
            <span class="form-tip" style="margin-left: 12px">越小越靠前</span>
          </el-form-item>
          <el-form-item label="状态" prop="status">
            <el-radio-group v-model="itemForm.status">
              <el-radio :value="1">启用</el-radio>
              <el-radio :value="0">禁用</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="备注">
            <el-input v-model="itemForm.remark" type="textarea" :rows="2" maxlength="255" />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="itemDialogVisible = false">取消</el-button>
          <el-button type="primary" :loading="itemSaving" @click="handleSubmitItem">确定</el-button>
        </template>
      </el-dialog>
    </el-drawer>

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getDictTypes, createDictType, updateDictType, deleteDictType,
  getDictItems, createDictItem, updateDictItem, deleteDictItem,
  type DictType, type DictItem
} from '@/api/dict'

// ══════════════════════════════════════════════════
//  字典类型
// ══════════════════════════════════════════════════
const typeLoading = ref(false)
const typeSaving = ref(false)
const typeList = ref<DictType[]>([])

async function fetchTypes() {
  typeLoading.value = true
  try {
    const res = await getDictTypes()
    typeList.value = (res as any).data || []
  } catch (e) {
    console.error('加载字典类型失败', e)
  } finally {
    typeLoading.value = false
  }
}

onMounted(fetchTypes)

// ── 新增/编辑弹窗 ──
const typeDialogVisible = ref(false)
const isEditType = ref(false)
const editTypeId = ref<number | null>(null)
const typeFormRef = ref<FormInstance>()

const typeForm = reactive({
  code: '',
  name: '',
  status: 1,
  remark: '',
})

const typeRules: FormRules = {
  code: [{ required: true, message: '请输入字典编码', trigger: 'blur' }],
  name: [{ required: true, message: '请输入字典名称', trigger: 'blur' }],
}

function openTypeDialog(row?: DictType) {
  if (row) {
    isEditType.value = true
    editTypeId.value = row.id!
    typeForm.code = row.code
    typeForm.name = row.name
    typeForm.status = row.status
    typeForm.remark = row.remark || ''
  } else {
    isEditType.value = false
    editTypeId.value = null
    typeForm.code = ''
    typeForm.name = ''
    typeForm.status = 1
    typeForm.remark = ''
  }
  typeDialogVisible.value = true
}

async function handleSubmitType() {
  const valid = await typeFormRef.value?.validate().catch(() => false)
  if (!valid) return

  typeSaving.value = true
  try {
    if (isEditType.value && editTypeId.value) {
      await updateDictType(editTypeId.value, {
        name: typeForm.name,
        status: typeForm.status,
        remark: typeForm.remark,
      })
      ElMessage.success('字典类型更新成功')
    } else {
      await createDictType({ ...typeForm })
      ElMessage.success('字典类型创建成功')
    }
    typeDialogVisible.value = false
    await fetchTypes()
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '操作失败')
  } finally {
    typeSaving.value = false
  }
}

async function handleDeleteType(id: number) {
  try {
    await deleteDictType(id)
    ElMessage.success('字典类型已删除')
    await fetchTypes()
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '删除失败')
  }
}

// ══════════════════════════════════════════════════
//  字典数据项（抽屉）
// ══════════════════════════════════════════════════
const itemDrawerVisible = ref(false)
const itemLoading = ref(false)
const itemSaving = ref(false)
const currentType = ref<DictType | null>(null)
const itemList = ref<DictItem[]>([])

async function openItemDrawer(type: DictType) {
  currentType.value = type
  itemDrawerVisible.value = true
  await fetchItems()
}

async function fetchItems() {
  if (!currentType.value) return
  itemLoading.value = true
  try {
    const res = await getDictItems(currentType.value.code)
    itemList.value = (res as any).data || []
  } catch (e) {
    console.error('加载字典值失败', e)
  } finally {
    itemLoading.value = false
  }
}

// ── 字典值 新增/编辑弹窗 ──
const itemDialogVisible = ref(false)
const isEditItem = ref(false)
const editItemId = ref<number | null>(null)
const itemFormRef = ref<FormInstance>()

const itemForm = reactive({
  label: '',
  value: '',
  sort: 0,
  status: 1,
  remark: '',
})

const itemRules: FormRules = {
  label: [{ required: true, message: '请输入显示标签', trigger: 'blur' }],
  value: [{ required: true, message: '请输入存储值', trigger: 'blur' }],
}

function openItemDialog(row?: DictItem) {
  if (row) {
    isEditItem.value = true
    editItemId.value = row.id!
    itemForm.label = row.label
    itemForm.value = row.value
    itemForm.sort = row.sort
    itemForm.status = row.status
    itemForm.remark = row.remark || ''
  } else {
    isEditItem.value = false
    editItemId.value = null
    itemForm.label = ''
    itemForm.value = ''
    itemForm.sort = 0
    itemForm.status = 1
    itemForm.remark = ''
  }
  itemDialogVisible.value = true
}

async function handleSubmitItem() {
  const valid = await itemFormRef.value?.validate().catch(() => false)
  if (!valid) return

  itemSaving.value = true
  try {
    if (isEditItem.value && editItemId.value) {
      await updateDictItem(editItemId.value, { ...itemForm })
      ElMessage.success('字典值更新成功')
    } else {
      await createDictItem({
        type_code: currentType.value!.code,
        ...itemForm,
      })
      ElMessage.success('字典值创建成功')
    }
    itemDialogVisible.value = false
    await fetchItems()
    // 刷新类型列表以更新 items_count
    await fetchTypes()
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '操作失败')
  } finally {
    itemSaving.value = false
  }
}

async function handleItemStatusChange(row: DictItem) {
  try {
    await updateDictItem(row.id!, { status: row.status })
    ElMessage.success(row.status === 1 ? '已启用' : '已禁用')
  } catch {
    row.status = row.status === 1 ? 0 : 1
    ElMessage.error('状态更新失败')
  }
}

async function handleDeleteItem(id: number) {
  try {
    await deleteDictItem(id)
    ElMessage.success('字典值已删除')
    await fetchItems()
    await fetchTypes()
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '删除失败')
  }
}
</script>

<style scoped>
.dict-page {
  width: 100%;
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.drawer-toolbar {
  margin-bottom: 16px;
}
.form-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.4;
  margin-top: 4px;
}
</style>
