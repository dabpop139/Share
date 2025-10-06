<template>
    <div id="app">
        <el-config-provider :locale="elLocale">
        <el-container class="fheight">
            <el-header class="eheader">
                <div class="layout-top">
                    <div class="disflex left-opts">
                        <div class="flex center elogo" style="display:none;">
                            <!-- <el-button type="info" size="small" icon="el-icon-location" circle></el-button> -->
                            <el-button type="info" size="small" round><!-- !!! --></el-button>
                        </div>
                        <div class="flex center etopfold">
                            <span class="inblock">
                                <a href="javascript:;" class="fa fa-align-justify" @click="toggleSideMemu"></a>
                            </span>
                        </div>
                        <div class="flex center etopnav">
                            <span class="inblock ml-3">
                                SQLift
                            </span>
                        </div>
                    </div>
                    <div class="disflex right-opts mt-2">
                        <span class="version-tip">
                            {{$configSite.version}}
                        </span>
                        <div class="switch-language">
                            <a href="javascript:;" @click="changeLanguage" v-if="locale=='zhCN'">Switch English</a>
                            <a href="javascript:;" @click="changeLanguage" v-if="locale=='en'">切换 中文</a>
                        </div>
                        <div class="useropts">
                            <a href="javascript:;" @click="doLogout">{{ t('message.logout') }}</a>
                        </div>
                    </div>
                </div>
            </el-header>
            <el-container class="fheight">
                <el-aside class="easide" width="230px" v-if="sideMemuVisible">
                    <el-scrollbar>
                        <!-- <div class="api-searcher">
                            <el-input
                                placeholder="关键词"
                                v-model="qKeyword"
                                @keyup.enter.native="searchServer">
                                <i slot="prefix" class="el-input__icon el-icon-search"></i>
                            </el-input>
                        </div>
                        <div class="mclear"></div>
                        <div class="api-toolbar">
                            <div class="mclear"></div>
                        </div> -->
                        <div class="api-catalog">
                            <el-tabs v-model="navTabActive" class="enavtree-tabs">
                                <el-tab-pane :label="t('message.all')" name="all">
                                    <div class="api-searcher">
                                        <el-select v-model="qKeyword" filterable :placeholder="t('message.keyword')" @change="searchSelect">
                                            <el-option
                                                v-for="(item, index) in subCatesAll"
                                                :key="index"
                                                :label="item.dbname"
                                                :value="item.dbname">
                                            </el-option>
                                        </el-select>
                                    </div>

                                    <el-tree
                                    ref="treeRef"
                                    node-key="id"
                                    :accordion="true"
                                    :indent="8"
                                    :data="subCates"
                                    :props="treeDefaultProps"
                                    @node-click="handleTreeNodeClick"
                                    @node-contextmenu="handleTreeNodeRightClick">
                                    <template v-slot:default="{node, data}">
                                        <span class="el-tree-node__label" :title="data.dbname">{{data.dbname}}</span>
                                    </template>
                                    </el-tree>
                                    
                                    <!-- 右键菜单 -->
                                    <div 
                                        v-show="contextMenuVisible" 
                                        class="context-menu"
                                        :style="{ left: contextMenuX + 'px', top: contextMenuY + 'px' }"
                                        @click.stop>
                                        <div class="context-menu-item" @click="refreshNode">
                                            <i class="fa fa-refresh"></i> 刷新
                                        </div>
                                        <div class="context-menu-item" @click="deleteNode" v-if="!selectedNodeData.isroot">
                                            <i class="fa fa-trash"></i> 删除
                                        </div>
                                        <div class="context-menu-divider"></div>
                                        <div class="context-menu-item" @click="viewNodeDetails" v-if="!selectedNodeData.isroot">
                                            <i class="fa fa-info-circle"></i> 查看详情
                                        </div>
                                    </div>
                                </el-tab-pane>
                            </el-tabs>
                        </div><!-- api-catalog -->
                        <div class="mt-4">&nbsp;</div>
                        <div class="mt-4">&nbsp;</div>
                    </el-scrollbar><!-- el-scrollbar -->
                </el-aside>
                <el-main class="emain">
                    <div class="ectrlbar">
                        <div class="v1">
                            <span class="inblock ml-3"><!-- !!! --></span>
                        </div>
                        <div class="v2">
                            <!-- !!! -->
                        </div>
                        <div class="mclear"></div>
                    </div>
                    <div class="" style="height:5px;"></div>
                    <!-- @tab-remove="removeTab" -->
                    <el-tabs v-model="mainTabsCurr" type="card" class="main-tab" :closable="true" @tab-click="switchTab">
                        <el-tab-pane
                            v-for="(etabItem, index) in editableTabs"
                            :key="etabItem.name"
                            :label="etabItem.title"
                            :name="etabItem.name"
                        >
                            <div class="main-area panel" :elem-index="index">
                                <div class="panel-title">
                                    <div class="mb-2">
                                        <el-alert
                                            :show-icon="false"
                                            :closable="false"
                                            type="error">
                                            <template #title>
                                                <div class="fsize15">
                                                    <i class="fa fa-slack" style="color:#F56C6C"></i>&nbsp;
                                                    <span v-html="currDatabaseTip"></span>
                                                </div>
                                            </template>
                                        </el-alert>
                                    </div>
                                </div><!-- panel-title -->
                                <div class="panel-content">
                                    <QueryPage
                                    v-model="mainTabsCurr"
                                    :maintabid="etabItem.aid"
                                    :maintabname="etabItem.name"
                                    :connention="currConnetion"
                                    :database="currDatabase"
                                    :datatable="currDataTable"
                                    />
                                </div><!-- panel-content -->
                            </div><!-- panel -->
                        </el-tab-pane>
                    </el-tabs>
                </el-main>

            </el-container>
        </el-container>
        </el-config-provider>
    </div>
</template>

<script setup>
import { nextTick, onBeforeMount, onMounted } from 'vue'
// import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import QueryPage from '@/components/QueryPage.vue'
import {
    ElConfigProvider,
    ElContainer, ElHeader, ElAside, ElScrollbar, ElMain, ElCol, ElRow,
    ElLoading, ElMessage, ElMessageBox, ElDialog, ElLink, ElBadge, ElAlert, ElTag,
    ElTree, ElMenu, /* ElMenuItemGroup, */ ElSubMenu, ElMenuItem,
    ElTabs, ElTabPane,
    ElDropdown, ElDropdownMenu, ElDropdownItem, ElButton, /* ElButtonGroup, */ ElUpload, ElInput, /* ElAutocomplete, */ ElSelect, ElOption, ElCheckboxGroup, ElCheckbox,
} from 'element-plus'

import COMJS from '@/libs/common'
import Utils from '@/libs/utils'

import zhCn from 'element-plus/dist/locale/zh-cn.mjs'

// const router = useRouter()
const { locale, t } = useI18n()
let elLocale = zhCn

let elMenuActive = $ref('0')
let cateOpeneds = $ref([0])

let subCatesAll = $ref({})
let subCates = $ref([])
let mainTabsCurr = $ref('MainTab0')
let maxTreeNodeId = $ref(0)
let editableTabs = $ref([
    {
        aid: 0,
        title: '新建接口',
        name: 'MainTab0',
    }
])

let navTabActive = $ref('all')
const treeRef = $ref(null)
const treeDefaultProps = {
    children: 'dlists',
    label: 'dbname',
}

let qKeyword = $ref('')
let searchKeyword = $ref('')

let sideMemuVisible = $ref(true)

let currMode = $ref('')

let currConnetion = $ref('')
let currDatabase = $ref('')
let currDataTable = $ref('')

let currDatabaseTip = $ref('SQLift')

// 右键菜单相关
let contextMenuVisible = $ref(false)
let contextMenuX = $ref(0)
let contextMenuY = $ref(0)
let selectedNodeData = $ref({})
let selectedNode = $ref(null)

// const treeIsLeaf = (node, data) => {
//     return !data.children
// }

const initData = () => {
    let params = {}
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/dsn',
        data: params,
    }, 'GET',
    response => {
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        if (typeof func === 'function') {
            func(resp.data)
            return false
        }
        // let respData = {}
        // resp.data.forEach((item, index) => {
        //     respData[item.id] = item
        // })
        let arrCates = []
        arrCates = resp.data
        subCates = arrCates
        maxTreeNodeId = arrCates[arrCates.length -1]['id']
        // console.log(maxTreeNodeId)
        return false
    },
    error => {
        return false
    })
}

const fetchDsn = (params, func) => {
    let getParams = {}
    // 根据params合并getParams
    if (typeof params === 'object') {
        getParams = Object.assign(getParams, params)
    }
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/fetch-dsn',
        data: getParams,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        if (typeof func === 'function') {
            func(resp)
            return false
        }
        return false
    },
    error => {
        loadingInstance.close()
        return false
    })
}

const fetchTables = (params, func) => {
    let getParams = {}
    // 根据params合并getParams
    if (typeof params === 'object') {
        getParams = Object.assign(getParams, params)
    }
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/fetch-tables',
        data: getParams,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        if (typeof func === 'function') {
            func(resp)
            return false
        }
        return false
    },
    error => {
        loadingInstance.close()
        return false
    })
}

const deleteDatabase = (params) => {
    let postParams = {}
    if (!params.database) {
        ElMessage.error('暂不支持删除数据库')
        return false
    }
    if (params.database) {
        postParams = {
            connetion: params.host,
            database: params.database,
            datatable: params.dbname,
        }
    } else {
        postParams = {
            connetion: params.host,
            database: params.dbname,
        }
    }
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/database-delete',
        data: postParams,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        ElMessage.success('删除成功')
        // 刷新节点
        refreshNode()
        return false
    },
    error => {
        loadingInstance.close()
        return false
    })
}

const handleTreeNodeClick = (node) => {
    // 隐藏右键菜单
    contextMenuVisible = false
    
    // console.log(node)
    if (node.dlists) {
        if (node.dlists.length > 0) {
            return
        }
        if (node.isroot) {
            fetchDsn({
                lastid: maxTreeNodeId,
                connetion: node.dbname,
            }, (resp) => {
                // 使用展开运算符触发Vue的响应式更新
                node.dlists = resp.data
                maxTreeNodeId = resp.data[resp.data.length -1]['id']
                // 通过重新赋值整个数组来确保视图更新
                subCates = subCates.slice()
            })
        }
        return
    }
    currConnetion = node.host
    currDatabase = node.database
    currDataTable = node.dbname
    currDatabaseTip = 'Table:'+node.database+' &gt; '+node.dbname+'&nbsp;&nbsp;&nbsp;Engine:'+node.engine+'&nbsp;&nbsp;&nbsp;Rows:'+node.rows
    openLink(node)
}

// 右键菜单处理
const handleTreeNodeRightClick = (event, data, node) => {
    event.preventDefault()
    selectedNodeData = data
    selectedNode = node
    
    // 计算菜单位置
    contextMenuX = event.clientX
    contextMenuY = event.clientY
    
    // 显示菜单
    contextMenuVisible = true
    
    // 监听页面点击事件，点击其他地方隐藏菜单
    const hideContextMenu = () => {
        contextMenuVisible = false
        document.removeEventListener('click', hideContextMenu)
    }
    
    setTimeout(() => {
        document.addEventListener('click', hideContextMenu)
    }, 100)
}

// 刷新节点
const refreshNode = () => {
    contextMenuVisible = false
    if (selectedNodeData.isroot) {
        // 刷新数据库连接
        fetchDsn({
            lastid: maxTreeNodeId,
            connetion: selectedNodeData.dbname,
        }, (resp) => {
            selectedNodeData.dlists = resp.data
            treeRef.updateKeyChildren(selectedNodeData.id, resp.data)
            // subCates = subCates.slice()
            maxTreeNodeId = resp.data[resp.data.length -1]['id']
            ElMessage.success('刷新成功')
        })
    } else {
        let fooDatabase = ''
        if (selectedNodeData.host  && !selectedNodeData.database) { // 定位在数据库的刷新
            fooDatabase = selectedNodeData.dbname
        } else { // 定位在表的刷新
            fooDatabase = selectedNodeData.database
        }

        // 刷新数据库表
        fetchTables({
            lastid: maxTreeNodeId,
            connetion: selectedNodeData.host,
            database: fooDatabase,
        }, (resp) => {
            // 找到并更新特定的数据节点
            subCates.forEach((item) => {
                if (item.dbname == selectedNodeData.host) {
                    item.dlists.forEach((item2) => {
                        if (item2.dbname == fooDatabase) {
                            item2.dlists = resp.data
                        }
                    })
                }
            })
            subCates = subCates.slice()
            maxTreeNodeId = resp.data[resp.data.length -1]['id']
            ElMessage.success('刷新成功')
        })
    }
}

// 删除节点
const deleteNode = () => {
    contextMenuVisible = false
    ElMessageBox.confirm(
        '确定要删除 "' + selectedNodeData.dbname + '" 吗？',
        '确认删除',
        {
            type: 'warning',
            confirmButtonText: '是',
            cancelButtonText: '否'
        }
    ).then(() => {
        deleteDatabase(selectedNodeData)
    }).catch(() => {
        // 用户取消删除
    })
}

// 查看节点详情
const viewNodeDetails = () => {
    contextMenuVisible = false
    console.log(selectedNode)
    console.log(selectedNodeData)
}

const doLogout = () => {
    localStorage.removeItem('uticket')
    window.location.reload()
}

const changeLanguage = () => {
    return
    if (briefNum.wait > 0) {
        ElMessageBox.alert(t('message.hasWaiting'), t('message.tip'), {})
        return
    }
    // console.log(locale.value)
    if (locale.value == 'zhCN') {
        locale.value = 'en'
        localStorage.setItem('language', 'en')
    } else {
        locale.value = 'zhCN'
        localStorage.setItem('language', 'zhCN')
    }
    // router.push({
    //     path: '/',
    //     query: {rand: Date.parse(new Date())}
    // })
    window.location.reload()
}

const initLanguage = () => {
    let language = localStorage.getItem('language')
    if (language !== null) {
        locale.value = language
        if (language == 'en') {
            elLocale = null
        }
    }
}

const toggleSideMemu = () => {
    sideMemuVisible = !sideMemuVisible;
}

const addTab = (node) =>{
    // 关闭帮助提示
    if (node.id != 0) {
        let hasIs = false
        editableTabs.forEach((tab, index) => {
            if (tab.aid === node.id) {
                hasIs = true
                mainTabsCurr = tab.name
                return
            }
        })
        if (hasIs) {
            switchTab()
            return
        }
    }
    let newTabName = 'MainTab'+node.id
    // 随机100到999整数
    editableTabs.push({
        aid: node.id,
        host: node.host,
        database: node.database,
        dbname: node.dbname,
        engine: node.engine,
        rows: node.rows,
        title: node.host+' > '+node.database+'('+node.id+')',
        name: newTabName,
    })
    mainTabsCurr = newTabName
}

const removeTab = (targetName) => {
    let tabs = editableTabs
    let activeName = mainTabsCurr
    if (activeName === targetName) {
        tabs.forEach((tab, index) => {
            if (tab.name === targetName) {
                let nextTab = tabs[index + 1] || tabs[index - 1]
                if (nextTab) {
                    activeName = nextTab.name;
                }
            }
        })
    }

    mainTabsCurr = activeName
    editableTabs = tabs.filter(tab => tab.name !== targetName)
    switchTab()
}

const switchTab = () => {
    setTimeout(() => {
        editableTabs.forEach((tab, index) => {
            if (tab.name === mainTabsCurr) {
                // !!!TODO 看如何优化这里的重复逻辑
                if (tab.aid == 0) {
                    currDatabaseTip = 'SQLift'
                } else {
                    currConnetion = tab.host
                    currDatabase = tab.database
                    currDataTable = tab.dbname
                    currDatabaseTip = 'Table:'+tab.database+' &gt; '+tab.dbname+'&nbsp;&nbsp;&nbsp;Engine:'+tab.engine+'&nbsp;&nbsp;&nbsp;Rows:'+tab.rows
                }
                return
            }
        })
    }, 80)
}

const openLink = (node) => {
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})

    setTimeout(() => {
        loadingInstance.close()
        addTab(node)
    }, 200)
}

const searchSelect = (value) => {
    qKeyword = ''
    // selectNavItem(value)
}

const keyboardEvent = (e) => {
    if ( e.altKey && e.key == 'q') {
        e.preventDefault()
        removeTab(mainTabsCurr)
        console.log('RemoveTab')
        e.returnValue = false
        return false
    }
}

onBeforeMount(() => {
    initData()
    initLanguage()
})

onMounted(() => {
    window.treeRef = treeRef
    document.addEventListener('keydown', keyboardEvent)
})
</script>