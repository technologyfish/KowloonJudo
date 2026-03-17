import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useRegistrationStore = defineStore('registration', () => {
  // 当前表单数据
  const formData = ref({
    site_id: null,       // 赛事站点ID
    name_pinyin: '',     // 姓名（拼音）
    name_cn: '',         // 姓名（汉字）
    phone: '',           // 手机号码
    email: '',           // 邮箱
    nationality: '',     // 国籍
    gender: '',          // 性别
    id_type: 'id_card',  // 证件类型：id_card=身份证, passport=护照
    id_card: '',         // 身份证号码
    passport_no: '',     // 护照号码
    birthday: '',        // 出生年月日
    age_group: '',       // 年龄组别
    belt_color: '',      // 带色
    weight_gi: '',       // 体重组别（道服）
    gi_open: false,      // 加报道服无差组别
    weight_nogi: '',     // 体重组别（无道服）
    nogi_open: false,    // 加报无道服无差组别
    team: '',            // 战队
  })

  // 当前选择的套餐
  const selectedPackage = ref(null)

  // 订单列表
  const orders = ref([])

  function setFormData(data) {
    formData.value = { ...formData.value, ...data }
  }

  function setSelectedPackage(pkg) {
    selectedPackage.value = pkg
  }

  function setOrders(list) {
    orders.value = list
  }

  function resetForm() {
    formData.value = {
      site_id: null,
      name_pinyin: '', name_cn: '', phone: '', email: '',
      nationality: '', gender: '', id_type: 'id_card', id_card: '', passport_no: '', birthday: '',
      age_group: '', belt_color: '',
      weight_gi: '', gi_open: false,
      weight_nogi: '', nogi_open: false,
      team: '',
    }
    selectedPackage.value = null
  }

  return {
    formData, selectedPackage, orders,
    setFormData, setSelectedPackage, setOrders, resetForm
  }
})
