<template>
  <scroll-view class="page" scroll-y :scroll-into-view="scrollToField" scroll-with-animation>

    <!-- ⓪ 隐私授权弹窗 -->
    <view v-if="showPrivacy" class="privacy-mask" @touchmove.stop.prevent>
      <view class="privacy-sheet">
        <text class="privacy-sheet-title">COPADECHN 体育健身小程序道隐私保护提示</text>
        <view class="privacy-sheet-body">
          <text class="privacy-sheet-text">为更好保护您的个人信息安全，请您仔细阅读并理解</text>
          <text class="privacy-sheet-link" @click="viewPrivacy">《COPADECHN 体育健身小程序隐私保护指引》</text>
          <text class="privacy-sheet-text">如您已阅读并同意上述条款，请点击"同意并继续"开始使用我们的服务</text>
        </view>
        <view class="privacy-sheet-btns">
          <button class="privacy-btn-view" @click="viewPrivacy">查看</button>
          <button class="privacy-btn-agree" @click="handleAgreePrivacy">同意并继续</button>
        </view>
      </view>
    </view>

    <!-- ① 比赛规则富文本 -->
    <view v-if="rule" class="rule-card">
      <view class="rule-header">
        <text class="rule-title">{{ rule.title }}</text>
        <text class="rule-date">{{ formatRuleDate(rule.created_at) }}</text>
      </view>
      <view class="rule-body">
        <rich-text :nodes="rule.content" />
      </view>
    </view>
    <view v-else-if="ruleLoading" class="rule-placeholder">
      <text>加载比赛规则...</text>
    </view>

    <!-- ② 报名表单 -->
    <view class="form-card">
      <view class="form-title-bar">
        <text class="form-title">信息提交</text>
        <text class="form-required-tip">* 为必填项</text>
      </view>

      <!-- 赛事站点 -->
      <view class="form-item" id="field-site">
        <text class="label">赛事站点 <text class="required">*</text></text>
        <picker class="picker" :range="siteNames" @change="onSiteChange" :disabled="!sites.length">
          <view class="picker-view" :class="{ disabled: !sites.length }">
            <text :class="form.site_id ? 'picker-val' : 'picker-placeholder'">
              {{ currentSiteName || (sites.length ? '请选择赛事站点' : '暂无可选站点') }}
            </text>
            <text class="picker-arrow">▼</text>
          </view>
        </picker>
        <text v-if="formErrors.site" class="field-error">{{ formErrors.site }}</text>
      </view>

      <!-- 姓名（拼音） -->
      <view class="form-item" id="field-name">
        <text class="label">姓名（拼音）</text>
        <input class="input" v-model="form.name_pinyin" placeholder="请输入姓名（拼音）" @input="formErrors.name = ''" />
      </view>

      <!-- 姓名（汉字） -->
      <view class="form-item">
        <text class="label">姓名（汉字）</text>
        <input class="input" v-model="form.name_cn" placeholder="请输入姓名（汉字）" @input="formErrors.name = ''" />
        <text v-if="formErrors.name" class="field-error">{{ formErrors.name }}</text>
      </view>

      <!-- 手机号码 -->
      <view class="form-item" id="field-phone">
        <text class="label">手机号码 <text class="required">*</text></text>
        <input class="input" v-model="form.phone" type="number" placeholder="请输入手机号码" @blur="validateField('phone')" />
        <text v-if="formErrors.phone" class="field-error">{{ formErrors.phone }}</text>
      </view>

      <!-- 邮箱 -->
      <view class="form-item" id="field-email">
        <text class="label">邮箱 <text class="required">*</text></text>
        <input class="input" v-model="form.email" placeholder="请输入邮箱地址" @blur="validateField('email')" />
        <text v-if="formErrors.email" class="field-error">{{ formErrors.email }}</text>
      </view>

      <!-- 国籍 -->
      <view class="form-item" id="field-nationality">
        <text class="label">国籍 <text class="required">*</text></text>
        <input class="input" v-model="form.nationality" placeholder="请输入国籍" @input="formErrors.nationality = ''" />
        <text v-if="formErrors.nationality" class="field-error">{{ formErrors.nationality }}</text>
      </view>

      <!-- 性别 -->
      <view class="form-item" id="field-gender">
        <text class="label">性别 <text class="required">*</text></text>
        <picker class="picker" :range="genderOptions" @change="onGenderChange">
          <view class="picker-view">
            <text :class="form.gender ? 'picker-val' : 'picker-placeholder'">
              {{ form.gender || '请选择' }}
            </text>
            <text class="picker-arrow">▼</text>
          </view>
        </picker>
        <text v-if="formErrors.gender" class="field-error">{{ formErrors.gender }}</text>
      </view>

      <!-- 身份证号码 -->
      <view class="form-item" id="field-id_card">
        <text class="label">身份证号码 <text class="required">*</text></text>
        <input class="input" v-model="form.id_card" placeholder="请输入身份证号码" @blur="validateField('id_card')" />
        <text v-if="formErrors.id_card" class="field-error">{{ formErrors.id_card }}</text>
      </view>

      <!-- 出生年月日 -->
      <view class="form-item" id="field-birthday">
        <text class="label">出生日期 <text class="required">*</text></text>
        <picker class="picker" mode="date" :start="birthdayRange.start" :end="birthdayRange.end" @change="onBirthdayChange">
          <view class="picker-view">
            <text :class="form.birthday ? 'picker-val' : 'picker-placeholder'">
              {{ form.birthday || '请选择出生日期' }}
            </text>
            <text class="picker-arrow">▼</text>
          </view>
        </picker>
        <text v-if="formErrors.birthday" class="field-error">{{ formErrors.birthday }}</text>
      </view>

      <!-- 年龄组别 -->
      <view class="form-item" id="field-age_group">
        <text class="label">年龄组别 <text class="required">*</text></text>
        <picker class="picker" :range="ageGroupOptions" @change="onAgeGroupChange" :disabled="!ageGroupOptions.length">
          <view class="picker-view" :class="{ disabled: !form.birthday }">
            <text :class="form.age_group ? 'picker-val' : 'picker-placeholder'">
              {{ form.age_group || (form.birthday ? '请选择' : '请先选择出生日期') }}
            </text>
            <text class="picker-arrow">▼</text>
          </view>
        </picker>
        <text v-if="formErrors.age_group" class="field-error">{{ formErrors.age_group }}</text>
        <view v-if="form.birthday && ageGroupOptions.length > 1" class="age-group-hint">
          <text class="hint-text">可选择降组参赛</text>
        </view>
      </view>

      <!-- 带色 -->
      <view class="form-item" id="field-belt_color">
        <text class="label">带色 <text class="required">*</text></text>
        <picker class="picker" :range="beltColorOptions" @change="onBeltChange" :disabled="!form.age_group">
          <view class="picker-view" :class="{ disabled: !form.age_group }">
            <text :class="form.belt_color ? 'picker-val' : 'picker-placeholder'">
              {{ form.belt_color || (form.age_group ? '请选择' : '请先选择年龄组别') }}
            </text>
            <text class="picker-arrow">▼</text>
          </view>
        </picker>
        <text v-if="formErrors.belt_color" class="field-error">{{ formErrors.belt_color }}</text>
      </view>

      <!-- ═══ 体重组别（GI 道服）═══ -->
      <view class="form-item weight-section" id="field-weight">
        <text class="label">体重组别（道服 GI）</text>
        <picker
          v-if="giWeights.length"
          class="picker"
          :range="giWeights"
          @change="onGiWeightChange"
        >
          <view class="picker-view">
            <text :class="form.weight_gi ? 'picker-val' : 'picker-placeholder'">
              {{ form.weight_gi || '请选择体重级别（可选）' }}
            </text>
            <text class="picker-arrow">▼</text>
          </view>
        </picker>
        <view v-else class="picker">
          <view class="picker-view disabled">
            <text class="picker-placeholder">请先选择年龄组别和性别</text>
          </view>
        </view>

        <!-- 成人组：加报道服无差组别 -->
        <view v-if="showOpenOption && form.weight_gi" class="open-toggle" @click="form.gi_open = !form.gi_open">
          <view class="open-check" :class="{ checked: form.gi_open }">
            <text v-if="form.gi_open" class="open-check-icon">✓</text>
          </view>
          <text class="open-text">道服无差组别</text>
          <text class="open-fee">+¥{{ feeConfig.open_weight_fee }}</text>
        </view>
      </view>

      <!-- ═══ 体重组别（NO-GI 无道服）═══ -->
      <view class="form-item weight-section">
        <text class="label nogi-label">体重组别（无道服 NO-GI）</text>
        <picker
          v-if="nogiWeights.length"
          class="picker"
          :range="nogiWeights"
          @change="onNogiWeightChange"
        >
          <view class="picker-view">
            <text :class="form.weight_nogi ? 'picker-val' : 'picker-placeholder'">
              {{ form.weight_nogi || '请选择体重级别（可选）' }}
            </text>
            <text class="picker-arrow">▼</text>
          </view>
        </picker>
        <view v-else class="picker">
          <view class="picker-view disabled">
            <text class="picker-placeholder">请先选择年龄组别和性别</text>
          </view>
        </view>

        <!-- 成人组：加报无道服无差组别 -->
        <view v-if="showOpenOption && form.weight_nogi" class="open-toggle" @click="form.nogi_open = !form.nogi_open">
          <view class="open-check" :class="{ checked: form.nogi_open }">
            <text v-if="form.nogi_open" class="open-check-icon">✓</text>
          </view>
          <text class="open-text">无道服无差组别</text>
          <text class="open-fee">+¥{{ feeConfig.open_weight_fee }}</text>
        </view>
      </view>

      <!-- 至少选一种提示 -->
      <view v-if="formErrors.weight" class="weight-tip">
        <text class="field-error" style="padding-left: 30rpx;">{{ formErrors.weight }}</text>
      </view>
      <view v-else-if="!form.weight_gi && !form.weight_nogi && form.age_group" class="weight-tip">
        <text class="weight-tip-text">* 道服和无道服至少选择一项</text>
      </view>

      <!-- 战队 -->
      <view class="form-item" id="field-team">
        <text class="label">战队 <text class="required">*</text></text>
        <input class="input" v-model="form.team" placeholder="请输入所属战队" @input="formErrors.team = ''" />
        <text v-if="formErrors.team" class="field-error">{{ formErrors.team }}</text>
      </view>

      <!-- ═══ 费用小计 ═══ -->
      <view class="fee-summary" v-if="totalFee > 0">
        <view class="fee-summary-title">费用明细</view>
        <view v-if="form.weight_gi" class="fee-row">
          <text class="fee-label">道服组别</text>
          <text class="fee-val">¥{{ feeConfig.category_fee }}</text>
        </view>
        <view v-if="form.gi_open" class="fee-row">
          <text class="fee-label">道服无差组别</text>
          <text class="fee-val">¥{{ feeConfig.open_weight_fee }}</text>
        </view>
        <view v-if="form.weight_nogi" class="fee-row">
          <text class="fee-label">无道服组别</text>
          <text class="fee-val">¥{{ feeConfig.category_fee }}</text>
        </view>
        <view v-if="form.nogi_open" class="fee-row">
          <text class="fee-label">无道服无差组别</text>
          <text class="fee-val">¥{{ feeConfig.open_weight_fee }}</text>
        </view>
        <view class="fee-total-row">
          <text class="fee-total-label">合计</text>
          <text class="fee-total-val">¥{{ totalFee }}</text>
        </view>
      </view>

      <!-- 提交按钮 -->
      <view class="submit-wrap">
        <button class="submit-btn" :loading="submitting" @click="handleSubmit">
          {{ totalFee > 0 ? `提交并支付 ¥${totalFee}` : '提 交' }}
        </button>
      </view>
    </view>

  </scroll-view>
</template>

<script setup>
import { ref, computed, reactive, onMounted, nextTick } from 'vue'
import { useRegistrationStore } from '@/store/registration'
import { useUserStore } from '@/store/user'
import { getLatestRule, getFeeSettings, getActiveSites, submitRegistration, createPayOrder, queryPayResult } from '@/api/competition'
import { getGIWeights, getNOGIWeights, getBeltColors, AGE_GROUPS, isAdultGroup, getAvailableAgeGroups } from '@/utils/weightTable'

// ─── 隐私授权弹窗（localStorage 控制，首次打开弹出）──────────
const showPrivacy = ref(false)

onMounted(() => {
  try {
    const agreed = uni.getStorageSync('privacy_agreed')
    if (!agreed) {
      showPrivacy.value = true
    }
  } catch {
    showPrivacy.value = true
  }
})

function viewPrivacy() {
  // 优先打开微信后台配置的隐私保护指引
  if (typeof wx !== 'undefined' && wx.openPrivacyContract) {
    wx.openPrivacyContract({
      fail() {
        // 如果微信 API 调用失败，兜底跳转本地页面
        uni.navigateTo({ url: '/pages/agreement/privacy' })
      }
    })
  } else {
    uni.navigateTo({ url: '/pages/agreement/privacy' })
  }
}

function handleAgreePrivacy() {
  uni.setStorageSync('privacy_agreed', '1')
  showPrivacy.value = false
}

// ─── 比赛规则 ──────────────────────────────────────────────
const rule = ref(null)
const ruleLoading = ref(true)

/** 格式化规则日期为 YYYY-MM-DD HH:mm:ss */
function formatRuleDate(val) {
  if (!val) return ''
  const d = new Date(val)
  if (isNaN(d.getTime())) return val
  const p = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
}

// ─── 费用配置（从后台获取）────────────────────────────────────
const feeConfig = reactive({
  category_fee: 360,
  open_weight_fee: 80,
})

// ─── 赛事站点 ──────────────────────────────────────────────
const sites = ref([])         // [{ id, label, value }, ...]
const siteNames = computed(() => sites.value.map(s => s.label))
const currentSiteName = computed(() => {
  if (!form.value.site_id) return ''
  const s = sites.value.find(s => s.id === form.value.site_id)
  return s ? s.label : ''
})

function onSiteChange(e) {
  const idx = Number(e.detail.value)
  const s = sites.value[idx]
  if (s) {
    form.value.site_id = s.id
    formErrors.site = ''
  }
}

onMounted(async () => {
  // 并行获取规则 + 费用 + 站点
  const [ruleRes, feeRes, siteRes] = await Promise.allSettled([
    getLatestRule(),
    getFeeSettings(),
    getActiveSites(),
  ])

  if (ruleRes.status === 'fulfilled') {
    rule.value = ruleRes.value.data
  }
  if (feeRes.status === 'fulfilled') {
    const d = feeRes.value.data
    feeConfig.category_fee    = Number(d.category_fee) || 360
    feeConfig.open_weight_fee = Number(d.open_weight_fee) || 80
  }
  if (siteRes.status === 'fulfilled') {
    sites.value = siteRes.value.data || []
    // 如果只有一个站点，自动选中
    if (sites.value.length === 1) {
      form.value.site_id = sites.value[0].id
    }
  }

  ruleLoading.value = false
})

// ─── 表单数据 ──────────────────────────────────────────────
const regStore  = useRegistrationStore()
const userStore = useUserStore()
const form = ref({ ...regStore.formData })
const submitting = ref(false)

// ─── 字段级别错误 & 滚动定位 ─────────────────────────────────
const scrollToField = ref('')
const formErrors = reactive({
  site: '',
  name: '',
  phone: '',
  email: '',
  nationality: '',
  gender: '',
  id_card: '',
  birthday: '',
  age_group: '',
  belt_color: '',
  weight: '',
  team: '',
})

const genderOptions = ['男', '女']

// 出生日期范围（1940-2020）
const birthdayRange = {
  start: '1940-01-01',
  end: '2020-12-31',
}

// 根据出生年份动态计算可选年龄组别
const ageGroupOptions = computed(() => {
  if (!form.value.birthday) return []
  const year = new Date(form.value.birthday).getFullYear()
  return getAvailableAgeGroups(year)
})

// 带色根据年龄组别动态切换
const beltColorOptions = computed(() => getBeltColors(form.value.age_group))

// 动态体重选项
const giWeights   = computed(() => getGIWeights(form.value.age_group, form.value.gender))
const nogiWeights = computed(() => getNOGIWeights(form.value.age_group, form.value.gender))

// 是否显示无差组别选项（仅成人组）
const showOpenOption = computed(() => isAdultGroup(form.value.age_group))

// ─── 费用计算 ──────────────────────────────────────────────
const totalFee = computed(() => {
  let total = 0
  if (form.value.weight_gi)   total += feeConfig.category_fee
  if (form.value.weight_nogi) total += feeConfig.category_fee
  if (form.value.gi_open)     total += feeConfig.open_weight_fee
  if (form.value.nogi_open)   total += feeConfig.open_weight_fee
  return total
})

// ─── 选项变化处理 ──────────────────────────────────────────
function onBirthdayChange(e) {
  form.value.birthday = e.detail.value
  formErrors.birthday = ''
  formErrors.age_group = ''
  formErrors.belt_color = ''
  formErrors.weight = ''
  // 切换生日后重置年龄组别及下游选项
  form.value.age_group = ''
  form.value.belt_color = ''
  form.value.weight_gi = ''
  form.value.weight_nogi = ''
  form.value.gi_open = false
  form.value.nogi_open = false
  // 如果只有一个可选组别，自动选中
  const groups = getAvailableAgeGroups(new Date(e.detail.value).getFullYear())
  if (groups.length === 1) {
    form.value.age_group = groups[0]
  }
}

function onGenderChange(e) {
  form.value.gender = genderOptions[e.detail.value]
  formErrors.gender = ''
  formErrors.weight = ''
  form.value.weight_gi = ''
  form.value.weight_nogi = ''
  form.value.gi_open = false
  form.value.nogi_open = false
}

function onAgeGroupChange(e) {
  form.value.age_group = ageGroupOptions.value[e.detail.value]
  formErrors.age_group = ''
  formErrors.belt_color = ''
  formErrors.weight = ''
  form.value.belt_color = ''
  form.value.weight_gi = ''
  form.value.weight_nogi = ''
  form.value.gi_open = false
  form.value.nogi_open = false
}

function onBeltChange(e) {
  form.value.belt_color = beltColorOptions.value[e.detail.value]
  formErrors.belt_color = ''
}

function onGiWeightChange(e) {
  form.value.weight_gi = giWeights.value[e.detail.value]
  formErrors.weight = ''
}

function onNogiWeightChange(e) {
  form.value.weight_nogi = nogiWeights.value[e.detail.value]
  formErrors.weight = ''
}

// ─── 清除GI体重 ──────────────────────────────────────────
function clearGiWeight() {
  form.value.weight_gi = ''
  form.value.gi_open = false
}

function clearNogiWeight() {
  form.value.weight_nogi = ''
  form.value.nogi_open = false
}

// ─── 正则 ────────────────────────────────────────────────────
const PHONE_RE  = /^1[3-9]\d{9}$/
const EMAIL_RE  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const IDCARD_RE = /^[1-9]\d{5}(19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/

/**
 * 单字段校验（blur 时触发）
 */
function validateField(field) {
  const v = String(form.value[field] || '').trim()
  if (field === 'phone') {
    if (!v) { formErrors.phone = '请填写手机号码'; return false }
    if (!PHONE_RE.test(v)) { formErrors.phone = '手机号格式不正确'; return false }
    formErrors.phone = ''
    return true
  }
  if (field === 'email') {
    if (!v) { formErrors.email = '请填写邮箱'; return false }
    if (!EMAIL_RE.test(v)) { formErrors.email = '邮箱格式不正确'; return false }
    formErrors.email = ''
    return true
  }
  if (field === 'id_card') {
    if (!v) { formErrors.id_card = '请填写身份证号码'; return false }
    if (!IDCARD_RE.test(v)) { formErrors.id_card = '身份证号格式不正确'; return false }
    formErrors.id_card = ''
    return true
  }
  return true
}

// ─── 表单验证（提交时）────────────────────────────────────────
function validate() {
  // 先清空所有错误
  Object.keys(formErrors).forEach(k => formErrors[k] = '')
  let firstError = ''

  // 赛事站点检查
  if (!form.value.site_id) {
    formErrors.site = '请选择赛事站点'
    if (!firstError) firstError = 'site'
  }

  // 姓名至少填一个
  const hasPinyin = String(form.value.name_pinyin || '').trim()
  const hasCn     = String(form.value.name_cn || '').trim()
  if (!hasPinyin && !hasCn) {
    formErrors.name = '姓名（拼音）和姓名（汉字）至少填写一项'
    if (!firstError) firstError = 'name'
  }

  // 必填项检查（一次性收集所有错误）
  const requiredFields = [
    { field: 'phone',       msg: '请填写手机号码' },
    { field: 'email',       msg: '请填写邮箱' },
    { field: 'nationality', msg: '请填写国籍' },
    { field: 'gender',      msg: '请选择性别' },
    { field: 'id_card',     msg: '请填写身份证号码' },
    { field: 'birthday',    msg: '请选择出生年月日' },
    { field: 'age_group',   msg: '请选择年龄组别' },
    { field: 'belt_color',  msg: '请选择带色' },
    { field: 'team',        msg: '请填写战队' },
  ]
  for (const r of requiredFields) {
    if (!form.value[r.field]) {
      formErrors[r.field] = r.msg
      if (!firstError) firstError = r.field
    }
  }

  // 正则校验（仅当字段非空时做格式校验，覆盖上面的必填提示）
  const v_phone = String(form.value.phone || '').trim()
  if (v_phone && !PHONE_RE.test(v_phone)) {
    formErrors.phone = '手机号格式不正确'
    if (!firstError) firstError = 'phone'
  }
  const v_email = String(form.value.email || '').trim()
  if (v_email && !EMAIL_RE.test(v_email)) {
    formErrors.email = '邮箱格式不正确'
    if (!firstError) firstError = 'email'
  }
  const v_idcard = String(form.value.id_card || '').trim()
  if (v_idcard && !IDCARD_RE.test(v_idcard)) {
    formErrors.id_card = '身份证号格式不正确'
    if (!firstError) firstError = 'id_card'
  }

  // 至少选一种体重组别
  if (!formErrors.age_group && form.value.age_group && !form.value.weight_gi && !form.value.weight_nogi) {
    formErrors.weight = '请至少选择一种体重组别（道服或无道服）'
    if (!firstError) firstError = 'weight'
  }

  if (firstError) {
    // 滚动到第一个错误字段
    scrollToField.value = ''
    nextTick(() => { scrollToField.value = 'field-' + firstError })
    return false
  }

  return true
}

// ─── 提交 ─────────────────────────────────────────────────
async function handleSubmit() {
  if (!userStore.isLoggedIn) {
    uni.showModal({
      title: '请先登录',
      content: '报名需要登录微信账号，是否前往登录？',
      success(res) {
        if (res.confirm) uni.navigateTo({ url: '/pages/login/index' })
      }
    })
    return
  }
  if (!validate()) return
  submitting.value = true
  try {
    // 1. 提交报名
    const regRes = await submitRegistration(form.value)
    regStore.setFormData(form.value)
    const orderId = regRes.data.order_id

    // 2. 提交成功提示
    uni.showToast({ title: '提交成功', icon: 'success', duration: 1000 })

    // 3. 创建支付订单并调起微信支付
    const payRes = await createPayOrder({
      order_id: orderId,
    })
    const payParams = payRes.data

    await new Promise((resolve, reject) => {
      uni.requestPayment({
        provider: 'wxpay',
        timeStamp: payParams.timeStamp,
        nonceStr: payParams.nonceStr,
        package: payParams.package,
        signType: payParams.signType,
        paySign: payParams.paySign,
        success: resolve,
        fail: reject,
      })
    })

    // 4. 查询支付结果
    await queryPayResult(orderId)
    uni.showToast({ title: '支付成功！', icon: 'success' })
    setTimeout(() => {
      uni.switchTab({ url: '/pages/user/index' })
      regStore.resetForm()
    }, 1500)

  } catch (e) {
    if (e?.errMsg === 'requestPayment:fail cancel') {
      uni.showToast({ title: '已取消支付，可在订单中继续支付', icon: 'none' })
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
}

/* ── 隐私授权底部弹窗 ── */
.privacy-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 9999;
  display: flex;
  align-items: flex-end;
}
.privacy-sheet {
  width: 100%;
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  padding: 40rpx 40rpx calc(40rpx + env(safe-area-inset-bottom));
}
.privacy-sheet-title {
  display: block;
  font-size: 34rpx;
  font-weight: bold;
  color: #1a1a1a;
  margin-bottom: 24rpx;
}
.privacy-sheet-body {
  line-height: 1.8;
  margin-bottom: 36rpx;
}
.privacy-sheet-text {
  font-size: 28rpx;
  color: #666;
}
.privacy-sheet-link {
  font-size: 28rpx;
  color: #1677ff;
}
.privacy-sheet-btns {
  display: flex;
  gap: 24rpx;
}
.privacy-btn-view {
  flex: 1;
  height: 84rpx;
  line-height: 84rpx;
  text-align: center;
  font-size: 30rpx;
  color: #333;
  background: #f5f5f5;
  border-radius: 42rpx;
  border: none;
  &::after { border: none; }
}
.privacy-btn-agree {
  flex: 1.5;
  height: 84rpx;
  line-height: 84rpx;
  text-align: center;
  font-size: 30rpx;
  color: #fff;
  background: #e74c3c;
  border-radius: 42rpx;
  border: none;
  font-weight: 500;
  &::after { border: none; }
}

/* ── 规则卡片 ── */
.rule-card {
  margin: 24rpx 24rpx 0;
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
  box-shadow: 0 2rpx 12rpx rgba(0,0,0,.06);
}
.rule-header {
  display: flex;
  flex-direction: column;
  padding: 24rpx 28rpx 16rpx;
  border-bottom: 1rpx solid #f0f0f0;
}
.rule-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #222;
  line-height: 1.4;
}
.rule-date {
  font-size: 22rpx;
  color: #bbb;
  margin-top: 8rpx;
}
.rule-body {
  padding: 20rpx 28rpx 24rpx;
  font-size: 26rpx;
  color: #555;
  line-height: 1.8;
}
.rule-placeholder {
  text-align: center;
  padding: 40rpx;
  color: #bbb;
  font-size: 26rpx;
}

/* ── 表单卡片 ── */
.form-card {
  margin: 24rpx;
  background: #fff;
  border-radius: 16rpx;
  padding: 0 0 40rpx;
  box-shadow: 0 2rpx 12rpx rgba(0,0,0,.06);
}
.form-title-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 30rpx 30rpx 16rpx;
  border-bottom: 1rpx solid #f5f5f5;
}
.form-title {
  font-size: 34rpx;
  font-weight: bold;
  color: #222;
}
.form-required-tip {
  font-size: 22rpx;
  color: #e74c3c;
}

/* ── 表单项 ── */
.form-item {
  padding: 0 30rpx;
  border-bottom: 1rpx solid #f5f5f5;
}
.label {
  display: block;
  font-size: 26rpx;
  color: #555;
  padding-top: 24rpx;
  padding-bottom: 6rpx;
}
.nogi-label {
  color: #e6a817;
}
.required {
  color: #e74c3c;
  margin-left: 4rpx;
}
.input {
  width: 100%;
  height: 80rpx;
  font-size: 28rpx;
  color: #222;
  border: none;
  outline: none;
  box-sizing: border-box;
  padding-bottom: 16rpx;
}

/* ── 字段级错误 ── */
.field-hint {
  display: block;
  font-size: 22rpx;
  color: #999;
  padding: 4rpx 0 0;
}
.field-error {
  display: block;
  font-size: 22rpx;
  color: #e74c3c;
  padding: 0 0 16rpx;
  line-height: 1.4;
}
.picker {
  width: 100%;
}
.picker-view {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 80rpx;
  padding-bottom: 16rpx;

  &.disabled {
    opacity: 0.5;
    pointer-events: none;
  }
}
.picker-val {
  font-size: 28rpx;
  color: #222;
}
.picker-placeholder {
  font-size: 28rpx;
  color: #bbb;
}
.picker-arrow {
  font-size: 22rpx;
  color: #bbb;
}

/* ── 年龄组别可降组提示 ── */
.age-group-hint {
  padding: 8rpx 0 12rpx;
}
.hint-text {
  font-size: 22rpx;
  color: #e6a817;
}

/* ── 体重选择区块 ── */
.weight-section {
  padding-bottom: 20rpx;
}

/* ── 无差组别勾选 ── */
.open-toggle {
  display: flex;
  align-items: center;
  padding: 16rpx 0 8rpx;
  gap: 14rpx;
}
.open-check {
  width: 36rpx;
  height: 36rpx;
  border-radius: 8rpx;
  border: 3rpx solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: #fff;
  transition: all .15s;

  &.checked {
    background: #e74c3c;
    border-color: #e74c3c;
  }
}
.open-check-icon {
  font-size: 22rpx;
  color: #fff;
  font-weight: bold;
}
.open-text {
  font-size: 26rpx;
  color: #333;
  flex: 1;
}
.open-fee {
  font-size: 26rpx;
  font-weight: bold;
  color: #e74c3c;
}

/* ── 提示 ── */
.weight-tip {
  padding: 12rpx 30rpx 0;
}
.weight-tip-text {
  font-size: 24rpx;
  color: #e74c3c;
}

/* ── 费用小计 ── */
.fee-summary {
  margin: 24rpx 30rpx 0;
  background: #fafafa;
  border-radius: 12rpx;
  padding: 24rpx;
}
.fee-summary-title {
  font-size: 28rpx;
  font-weight: bold;
  color: #333;
  margin-bottom: 16rpx;
}
.fee-row {
  display: flex;
  justify-content: space-between;
  padding: 10rpx 0;
}
.fee-label {
  font-size: 26rpx;
  color: #666;
}
.fee-val {
  font-size: 26rpx;
  color: #333;
}
.fee-total-row {
  display: flex;
  justify-content: space-between;
  padding: 16rpx 0 0;
  margin-top: 12rpx;
  border-top: 1rpx solid #e0e0e0;
}
.fee-total-label {
  font-size: 30rpx;
  font-weight: bold;
  color: #333;
}
.fee-total-val {
  font-size: 34rpx;
  font-weight: bold;
  color: #e74c3c;
}

/* ── 按钮 ── */
.submit-wrap {
  padding: 40rpx 30rpx 0;
}
.submit-btn {
  width: 100%;
  height: 96rpx;
  line-height: 96rpx;
  background: #e74c3c;
  color: #fff;
  border-radius: 48rpx;
  font-size: 34rpx;
  font-weight: bold;
  border: none;
  text-align: center;
}
</style>
