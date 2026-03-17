<template>
  <div class="layout-wrapper">
    <!-- 上部：侧边栏 + 右侧主区域 -->
  <el-container class="layout-root">

    <!-- ── 侧边栏 ── -->
    <el-aside width="220px" class="layout-aside">
      <!-- Logo -->
      <div class="aside-logo">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" style="flex-shrink:0">
          <rect width="28" height="28" rx="7" fill="#1677ff"/>
          <path d="M14 5C9.029 5 5 9.029 5 14C5 18.971 9.029 23 14 23C18.971 23 23 18.971 23 14C23 9.029 18.971 5 14 5Z" stroke="white" stroke-width="1.8"/>
          <path d="M9.5 14L12.5 17L18.5 10.5" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="logo-text">COPA DE CHN</span>
      </div>

      <!-- 导航菜单 -->
      <el-menu
        :default-active="activeMenu"
        router
        class="side-menu"
        :collapse="false"
      >
        <el-menu-item index="/dashboard">
          <el-icon><Odometer /></el-icon>
          <span>控制台</span>
        </el-menu-item>

        <div class="menu-group-label">比赛管理</div>

        <el-menu-item index="/competition/rules">
          <el-icon><Document /></el-icon>
          <span>比赛规则</span>
        </el-menu-item>

        <el-menu-item index="/competition/registrations">
          <el-icon><List /></el-icon>
          <span>报名记录</span>
        </el-menu-item>

        <el-menu-item index="/competition/fee-settings">
          <el-icon><Money /></el-icon>
          <span>费用设置</span>
        </el-menu-item>

        <el-menu-item index="/bill/stats">
          <el-icon><Wallet /></el-icon>
          <span>账单统计</span>
        </el-menu-item>

        <el-menu-item index="/announcements">
          <el-icon><Bell /></el-icon>
          <span>公告管理</span>
        </el-menu-item>

        <div class="menu-group-label">系统管理</div>

        <el-menu-item index="/users">
          <el-icon><User /></el-icon>
          <span>用户管理</span>
        </el-menu-item>

          <el-menu-item index="/system/dict">
            <el-icon><Collection /></el-icon>
            <span>字典管理</span>
          </el-menu-item>
      </el-menu>
    </el-aside>

    <!-- ── 右侧主区域 ── -->
    <el-container class="layout-right">

      <!-- 顶部 Header -->
      <el-header class="layout-header">
        <div class="header-left">
          <!-- 面包屑 -->
          <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/dashboard' }">首页</el-breadcrumb-item>
            <el-breadcrumb-item v-if="currentTitle">{{ currentTitle }}</el-breadcrumb-item>
          </el-breadcrumb>
        </div>

        <div class="header-right">
          <el-divider direction="vertical" style="margin: 0 12px" />

          <el-dropdown @command="handleCommand" trigger="click">
            <div class="user-trigger">
              <el-avatar :size="30" style="background:#1677ff;flex-shrink:0">
                <el-icon size="16"><UserFilled /></el-icon>
              </el-avatar>
              <span class="username">{{ authStore.userInfo?.name || '管理员' }}</span>
              <el-icon size="12" style="color:#aaa"><ArrowDown /></el-icon>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item disabled>
                  <span style="font-size:12px;color:#999">{{ authStore.userInfo?.email }}</span>
                </el-dropdown-item>
                <el-dropdown-item divided command="logout" style="color:#f56c6c">
                  退出登录
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>

      <!-- 内容区 -->
      <el-main class="layout-main">
        <!-- 页面标题栏 -->
        <div v-if="currentTitle" class="page-header">
          <span class="page-header-title">{{ currentTitle }}</span>
        </div>

        <router-view />
      </el-main>
    </el-container>
  </el-container>

    <!-- 公共底部备案信息（全宽，文档流） -->
    <div class="global-footer">
      <span>备案号：</span>
      <a href="http://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer">桂ICP备2026003766号-1</a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import {
  Odometer, Document, List, User, Bell, Money, Collection, Wallet,
  UserFilled, ArrowDown
} from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { logout } from '@/api/auth'

const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore()

const activeMenu   = computed(() => route.path)
const currentTitle = computed(() => (route.meta.title as string) || '')

async function handleCommand(command: string) {
  if (command === 'logout') {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
      type: 'warning',
      confirmButtonText: '退出',
      confirmButtonClass: 'el-button--danger'
    })
    await logout().catch(() => {})
    authStore.logout()
    router.push('/login')
  }
}
</script>

<style scoped>
/* ── 最外层包裹：纵向 flex，撑满视口 ── */
.layout-wrapper {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}

/* ── 根容器（侧边栏 + 右侧）占满剩余高度 ── */
.layout-root {
  flex: 1;
  overflow: hidden;
}

/* ── 侧边栏 ── */
.layout-aside {
  background: #fff;
  border-right: 1px solid #f0f0f0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.aside-logo {
  height: 60px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 20px;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}
.logo-text {
  font-size: 16px;
  font-weight: 700;
  color: #1a1a1a;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

/* 分组标签 */
.menu-group-label {
  font-size: 11px;
  color: #bbb;
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 16px 20px 6px;
}

/* 菜单覆盖 */
.side-menu {
  border-right: none;
  flex: 1;
  overflow-y: auto;
  padding: 8px 10px;
}
:deep(.el-menu-item) {
  border-radius: 8px;
  margin-bottom: 2px;
  height: 42px;
  color: #555;
}
:deep(.el-menu-item:hover) {
  background: #f0f7ff !important;
  color: #1677ff !important;
}
:deep(.el-menu-item.is-active) {
  background: #e8f4ff !important;
  color: #1677ff !important;
  font-weight: 600;
}
:deep(.el-menu-item .el-icon) { color: inherit; }

/* ── 顶部 Header ── */
.layout-right { overflow: hidden; }

.layout-header {
  height: 60px;
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  flex-shrink: 0;
}

.header-left { display: flex; align-items: center; }

.header-right {
  display: flex;
  align-items: center;
}

.env-badge {
  font-size: 11px;
  color: #fa8c16;
  background: #fff7e6;
  border: 1px solid #ffd591;
  border-radius: 4px;
  padding: 2px 8px;
}

.user-trigger {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: background .2s;
}
.user-trigger:hover { background: #f5f5f5; }
.username { font-size: 14px; color: #333; }

/* ── 内容主区域 ── */
.layout-main {
  background: #f5f6fa;
  padding: 20px 24px;
  overflow-y: auto;
}

.page-header {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
}
.page-header-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
}

/* 面包屑颜色 */
:deep(.el-breadcrumb__inner) { color: #999; font-size: 13px; }
:deep(.el-breadcrumb__inner a) { color: #666; }
:deep(.el-breadcrumb__inner.is-link:hover) { color: #1677ff; }

/* ── 公共底部备案信息（全宽横跨） ── */
.global-footer {
  flex-shrink: 0;
  text-align: center;
  padding: 15px 0;
  font-size: 12px;
  color: #bbb;
  background: #fff;
  border-top: 1px solid #f0f0f0;
}
.global-footer a {
  color: #bbb;
  text-decoration: none;
  transition: color .2s;
}
.global-footer a:hover {
  color: #1677ff;
  text-decoration: underline;
}
</style>
