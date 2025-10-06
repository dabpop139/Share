/* eslint-disable */
const configSite = {
    version: 'v0.0.1',
    apicomm: 'http://localhost:8031/',
    
    install(Vue) {
        // Vue({configBase: configBase})
        // Vue.prototype.$configSite = configSite
        Vue.config.globalProperties.$configSite = configSite
        // console.log(Vue)
    }
}

export default configSite