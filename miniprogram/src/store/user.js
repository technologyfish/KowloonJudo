import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const TOKEN_KEY   = 'kowloon_token'
const USERINFO_KEY = 'kowloon_user'

function readStorage(key) {
  try { return uni.getStorageSync(key) || null } catch { return null }
}
function writeStorage(key, val) {
  try { uni.setStorageSync(key, val) } catch {}
}
function removeStorage(key) {
  try { uni.removeStorageSync(key) } catch {}
}

export const useUserStore = defineStore('user', () => {
  // 初始化时从本地存储读取
  const token    = ref(readStorage(TOKEN_KEY) || '')
  const userInfo = ref((() => {
    try {
      const raw = readStorage(USERINFO_KEY)
      return raw ? JSON.parse(raw) : null
    } catch { return null }
  })())

  const isLoggedIn = computed(() => !!token.value && !!userInfo.value)

  function setToken(val) {
    token.value = val
    writeStorage(TOKEN_KEY, val)
  }

  function setUserInfo(info) {
    userInfo.value = info
    writeStorage(USERINFO_KEY, info ? JSON.stringify(info) : '')
  }

  function logout() {
    token.value    = ''
    userInfo.value = null
    removeStorage(TOKEN_KEY)
    removeStorage(USERINFO_KEY)
  }

  return { token, userInfo, isLoggedIn, setToken, setUserInfo, logout }
})
