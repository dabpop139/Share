import { createApp } from 'vue'
import App from '@/App.vue'
import Home from '@/pages/Home.vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import configSite from '@/config/site'
import 'element-plus/dist/index.css'
import '@/assets/css/master.css'
import i18n from '@/i18n'

window.$configSite = configSite

const router = createRouter({
    history: createWebHashHistory(),
    routes: [{
        path: '/',
        component: Home,
        meta: {
            title: '',
            keepAlive: true,
        }
    }]
})
  
router.afterEach((to) => {

})

createApp(App).use(configSite).use(router).use(i18n).mount('#app')