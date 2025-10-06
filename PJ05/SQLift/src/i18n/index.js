import { createI18n } from 'vue-i18n'
import zhCN from '@/i18n/zhCN'
import en from '@/i18n/en'
const messages = {
    zhCN,
    en
}

let locale = 'zhCN'
let fallbackLocale = 'en'
let language = localStorage.getItem('language')
if (language === 'en' || window.location.href.indexOf('lang=en')!=-1) {
    locale = 'en'
    fallbackLocale = 'zhCN'
}

const i18n = createI18n({
    legacy: false, // 使用CompotitionAPI必须添加这条.
    locale: locale, // 默认语言
    fallbackLocale: fallbackLocale, // 默认第二语言
    messages
})

export default i18n