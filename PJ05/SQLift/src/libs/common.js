/* eslint-disable */
import i18n from '@/i18n'
import Qs from 'qs'
import { ElMessageBox, ElMessage } from 'element-plus'
import Axios from 'axios'
const { locale, t } = i18n.global

const CommJs = {
    newAxios () {
        let userToken = localStorage.getItem('uticket') ? localStorage.getItem('uticket') : ''
        return Axios.create({
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                // 'X-User-Token': userToken,
            },
        })
    },
    doAjax (option, actype, succfun, failfun, ipm) {
        let url = option.url
        let pdata = option.data
        let userToken = localStorage.getItem('uticket') ? localStorage.getItem('uticket') : ''
        let headersConfig = {}
        let httpConfig = {}
        headersConfig = {
            'X-Requested-With': 'XMLHttpRequest',
            // 'X-User-Token': userToken
        }
        httpConfig['headers'] = headersConfig
        
        let insAxios = null
        if (actype == 'GET') {
            insAxios = Axios.get(url, {
                params: pdata,
                headers: headersConfig
            })
        }
        if (actype == 'POST') {
            if (option.ctype == 'json'){
                httpConfig['headers']['Content-Type'] = 'application/json; charset=UTF-8'
                insAxios = Axios.post(url, pdata, httpConfig)
            }else{
                httpConfig['headers']['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8'
                insAxios = Axios.post(url, Qs.stringify(pdata), httpConfig)
            }
        }

        if (ipm === true) {
            return insAxios
        }

        if (insAxios) {
            insAxios.then(response => {
                if (response.data.code == 4007) {
                    ElMessageBox.alert('请登录后操作!!!', '错误 :-(', {})
                    localStorage.removeItem('uticket')
                    // localStorage.removeItem('userinfo')

                    ElMessage.closeAll()
                    return false
                }
                if (response.data.code == 4008) {
                    ElMessageBox.alert('你没有权限访问', '错误 :-(', {})
                    ElMessage.closeAll()
                    return false
                }
                if (typeof succfun === 'function') {
                    if (false === succfun(response)) {
                        return false
                    }
                }
                ElMessage.closeAll()
                if (response.data.code == 1) {
                    ElMessageBox('', response.data.msg !== '' ? t('message.'+response.data.msg) : t('message.operaSuccess'), {})
                    return true
                }
                ElMessageBox.alert(t('message.'+response.data.msg), t('message.alertError'), {})
            })
            .catch(error => {
                if (typeof failfun === 'function') {
                    if (false === failfun(error)) {
                        return false
                    }
                }
                ElMessage.closeAll()
                if (typeof error.response != 'undefined') {
                    if (error.response.status == 401) {
                        ElMessageBox.alert('请登录后操作!!!', '错误 :-(', {})
                        localStorage.removeItem('uticket')
                        // localStorage.removeItem('userinfo')
                        
                        return false
                    }
                    if (error.response.status == 403) {
                        ElMessageBox.alert('你没有权限访问', '错误 :-(', {})
                        return false
                    }
                }

                console.log(error)
                ElMessageBox.alert(t('message.alertServerError')+':'+url, t('message.alertError'), {})
            })
        }
        
    },
}

export default CommJs