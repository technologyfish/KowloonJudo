<template>
  <div class="login-page">
    <div class="login-card">
      <!-- Logo -->
      <div class="login-logo">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
          <rect width="40" height="40" rx="10" fill="#1677ff"/>
          <path d="M20 8C13.373 8 8 13.373 8 20C8 26.627 13.373 32 20 32C26.627 32 32 26.627 32 20C32 13.373 26.627 8 20 8Z" stroke="white" stroke-width="2.2"/>
          <path d="M14 20L18.5 24.5L26 15.5" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <h1 class="login-title">KowloonJudo</h1>
      <p class="login-subtitle">九龙柔道后台管理系统</p>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        size="large"
        class="login-form"
        @keyup.enter="handleLogin"
      >
        <el-form-item prop="email">
          <el-input
            v-model="form.email"
            placeholder="管理员邮箱"
            :prefix-icon="Message"
            clearable
          />
        </el-form-item>

        <el-form-item prop="password">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="登录密码"
            :prefix-icon="Lock"
            show-password
          />
        </el-form-item>

        <el-form-item style="margin-top:8px; margin-bottom:0">
          <el-button
            type="primary"
            class="login-btn"
            :loading="loading"
            @click="handleLogin"
          >
            {{ loading ? '登录中...' : '登 录' }}
          </el-button>
        </el-form-item>
      </el-form>

      <p class="login-footer">© 2025 KowloonJudo · All rights reserved</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { Message, Lock } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { login } from '@/api/auth'

const router   = useRouter()
const authStore = useAuthStore()
const formRef  = ref<FormInstance>()
const loading  = ref(false)

const form = reactive({ email: '', password: '' })

const rules: FormRules = {
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '邮箱格式不正确', trigger: 'blur' }
  ],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
}

async function handleLogin() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  loading.value = true
  try {
    const res: any = await login(form)
    const token = res?.data?.token || res?.token
    const user  = res?.data?.user  || res?.user
    if (!token) { ElMessage.error('登录失败，未获取到 Token'); return }
    authStore.setToken(token)
    authStore.setUserInfo(user)
    ElMessage.success('登录成功')
    router.push('/dashboard')
  } catch {
    // 错误已在拦截器统一处理
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #f0f2f5;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-card {
  width: 420px;
  background: #fff;
  border-radius: 12px;
  padding: 48px 44px 36px;
  box-shadow: 0 4px 24px rgba(0,0,0,.08);
  text-align: center;
}

.login-logo { margin-bottom: 18px; display: flex; justify-content: center; }

.login-title {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 6px;
}
.login-subtitle {
  font-size: 13px;
  color: #aaa;
  margin: 0 0 32px;
}

.login-form { text-align: left; }

.login-btn {
  width: 100%;
  height: 46px;
  font-size: 16px;
  letter-spacing: 4px;
  border-radius: 8px;
}

.login-footer {
  margin-top: 28px;
  font-size: 12px;
  color: #ccc;
}

:deep(.el-input__wrapper) {
  border-radius: 8px;
  height: 46px;
}
:deep(.el-form-item) { margin-bottom: 20px; }
</style>
