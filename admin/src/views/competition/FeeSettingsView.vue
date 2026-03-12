<template>
  <div class="fee-settings">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>费用设置</span>
          <el-text type="info" size="small">设置比赛报名的组别费用和无差别组别费用</el-text>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="formRules"
        label-width="160px"
        style="max-width: 500px"
        v-loading="loading"
      >
        <el-form-item label="组别费用（元）" prop="category_fee">
          <el-input-number
            v-model="form.category_fee"
            :min="0"
            :precision="2"
            :step="10"
            style="width: 220px"
          />
          <el-text type="info" size="small" style="margin-left: 12px">
            每选一个组别（道服/无道服）收取一次
          </el-text>
        </el-form-item>

        <el-form-item label="无差别组别费用（元）" prop="open_weight_fee">
          <el-input-number
            v-model="form.open_weight_fee"
            :min="0"
            :precision="2"
            :step="10"
            style="width: 220px"
          />
          <el-text type="info" size="small" style="margin-left: 12px">
            成人组可选加报无差组别，每次收取
          </el-text>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" :loading="saving" @click="handleSave">
            保存设置
          </el-button>
        </el-form-item>
      </el-form>

      <!-- 费用示例 -->
      <el-divider />
      <div class="fee-examples">
        <el-text type="info" tag="div" style="margin-bottom: 12px; font-weight: 600">费用计算示例</el-text>
        <el-descriptions :column="1" border size="small">
          <el-descriptions-item label="仅道服">
            ¥{{ form.category_fee }}
          </el-descriptions-item>
          <el-descriptions-item label="仅无道服">
            ¥{{ form.category_fee }}
          </el-descriptions-item>
          <el-descriptions-item label="道服 + 无道服">
            ¥{{ form.category_fee * 2 }}
          </el-descriptions-item>
          <el-descriptions-item label="道服 + 道服无差">
            ¥{{ form.category_fee + form.open_weight_fee }}
          </el-descriptions-item>
          <el-descriptions-item label="道服 + 无道服 + 道服无差">
            ¥{{ form.category_fee * 2 + form.open_weight_fee }}
          </el-descriptions-item>
          <el-descriptions-item label="道服 + 无道服 + 两项无差">
            ¥{{ form.category_fee * 2 + form.open_weight_fee * 2 }}
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { getFeeSettings, updateFeeSettings } from '@/api/competition'

const formRef = ref<FormInstance>()
const loading = ref(false)
const saving  = ref(false)

const form = reactive({
  category_fee: 360,
  open_weight_fee: 80,
})

const formRules: FormRules = {
  category_fee:    [{ required: true, message: '请输入组别费用', trigger: 'blur' }],
  open_weight_fee: [{ required: true, message: '请输入无差别组别费用', trigger: 'blur' }],
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await getFeeSettings()
    const data = (res as any).data?.data || (res as any).data
    form.category_fee    = Number(data.category_fee) || 360
    form.open_weight_fee = Number(data.open_weight_fee) || 80
  } catch (e) {
    console.error('加载费用设置失败', e)
  } finally {
    loading.value = false
  }
})

async function handleSave() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    await updateFeeSettings({
      category_fee: form.category_fee,
      open_weight_fee: form.open_weight_fee,
    })
    ElMessage.success('费用设置已保存')
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '保存失败')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.fee-settings {
  width: 100%;
}
.card-header {
  display: flex;
  align-items: center;
  gap: 16px;
}
.fee-examples {
  max-width: 500px;
}
</style>
