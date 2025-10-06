<template>
    <el-tabs v-model="panelTabsCurr" class="database-exec-tab">
        <el-tab-pane>
            <template #label><el-tag size="default" style="cursor:pointer;">查询</el-tag></template>
            <div class="queryexec-tool">
                <el-tag size="default" type="danger" style="cursor:pointer;margin-right:10px;" @click="submitQuery">执行</el-tag>
                <el-tag size="default" type="info" style="cursor:pointer;margin-right:10px;" @click="collapseQuery">折叠</el-tag>
                <el-tag size="default" type="info" style="cursor:pointer;" @click="showSaveQueryDialog">保存</el-tag>
            </div>
            <div :class="`queryEditorContainer-`+thatMainTabId">
                <div class="mt-2"></div>
                <codemirror v-model="queryBody"
                    :style="{ height: '250px' }"
                    :extensions="cmExtensionsQuery"
                />
            </div>
            <div class="mt-2"></div>
            <el-alert
                v-if="currQueryTip != ''"
                :show-icon="true"
                :closable="false"
                type="info">
                <template #title>
                    <div v-html="currQueryTip"></div>
                </template>
            </el-alert>
            <div class="mt-2"></div>
            <!--
            :getRowId="getRowId"
            @first-data-rendered="onFirstDataRendered"
            -->
            <ag-grid-vue class="ag-theme-alpine" style="height:calc(100vh - 93px);"
                :columnDefs="currTheaders"
                :defaultColDef="defaultColDef"
                :gridOptions="gridOptions"
                :excelStyles="excelStyles"
                :rowData="queryData"
                rowHeight="32"
                @grid-ready="onGridReady"
                @row-data-updated="onRowDataUpdated"
            >
            </ag-grid-vue>
        </el-tab-pane>
        <el-tab-pane>
            <template #label><el-tag size="default" style="cursor:pointer;" type="success" @click="showDDL">DDL</el-tag></template>
            <div>
                <codemirror
                v-model="ddlColumn"
                :style="{ height: '240px' }"
                :extensions="cmExtensions"
                />
            </div>
            <div class="mt-2 mb-2"><el-link type="primary" @click="ddlColumnCopyThat">复制</el-link></div>
            <div>
                <codemirror
                v-model="ddlBody"
                :style="{ height: 'calc(100vh - 560px)' }"
                :extensions="cmExtensions"
                />
            </div>
            <div class="mt-2"><el-link type="primary" @click="ddlBodyCopyThat">复制</el-link></div>
        </el-tab-pane>
        <el-tab-pane>
            <template #label><el-tag size="default" style="cursor:pointer;" type="warning" @click="showHistory">历史</el-tag></template>
            <div>
                <codemirror
                v-model="historyBody"
                :disabled="true"
                :style="{ height: 'calc(100vh - 250px)' }"
                :extensions="cmExtensions"
                />
            </div>
        </el-tab-pane>
        <el-tab-pane>
            <template #label><el-tag size="default" style="cursor:pointer;" @click="showSnippets">脚本</el-tag></template>
            <div>
                <el-collapse :accordion="true">
                    <el-collapse-item v-for="(item, index) in snippetData" :name="index" :key="index">
                        <template #title>
                            <span class="fsize14">
                                <i class="header-icon fa fa-terminal" style="color:#909399"></i>&nbsp;
                                {{item.title}}
                            </span>
                        </template>
                        <div>
                            <codemirror
                            v-model="item.content"
                            :style="{ height: '250px' }"
                            :extensions="cmExtensions"
                            />
                        </div>
                        <div class="mt-2"></div>
                        <el-link type="primary" @click="snippetDelete(item)">删除</el-link>&nbsp;&nbsp;&nbsp;
                        <el-link type="primary" @click="snippetCopy(item)">复制</el-link>
                    </el-collapse-item>
                </el-collapse>
            </div>
        </el-tab-pane>
        <el-tab-pane>
            <template #label><el-tag size="default" type="danger" style="cursor:pointer;" @click="showTableStructureEditor = true">表结构</el-tag></template>
            <div v-if="showTableStructureEditor">
                <TableStructureEditor
                :connention="currConnetion"
                :database="currDatabase"
                :datatable="currDataTable" />
            </div>
        </el-tab-pane>
        <el-tab-pane>
            <template #label><el-tag size="default" type="success" style="cursor:pointer;" @click="showTableIndexEditor = true">索引</el-tag></template>
            <div v-if="showTableIndexEditor">
                <TableIndexEditor
                :connention="currConnetion"
                :database="currDatabase"
                :datatable="currDataTable" />
            </div>
        </el-tab-pane>
    </el-tabs>
    <div style="height:40px;"></div>
    
    <el-dialog title="保存查询" width="590px" v-model="saveQueryDialogVisible">
        <div class="el-row">
            <div class="pl-4">查询名称：</div>
        </div>
        <div class="el-row mt-2 pl-4 pr-4">
            <el-input
                type="text"
                placeholder="请输入查询名称"
                v-model="saveQueryTitle">
            </el-input>
        </div>
        <div class="el-row mt-4 pl-4">
            <el-button type="primary" @click="saveQuery">保存</el-button>
        </div>
    </el-dialog>
</template>

<script setup>
import { onBeforeMount, onMounted, onBeforeUnmount } from 'vue'
// import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import TableStructureEditor from '@/components/TableStructureEditor.vue'
import TableIndexEditor from '@/components/TableIndexEditor.vue'
import {
    ElConfigProvider,
    ElScrollbar, ElCol, ElRow,
    ElLoading, ElMessage, ElMessageBox, ElDialog, ElLink, ElBadge, ElAlert, ElTag,
    ElTabs, ElTabPane, ElCollapse, ElCollapseItem, /* ElCard, ElDatePicker, */
    ElDropdown, ElDropdownMenu, ElDropdownItem, ElButton, /* ElButtonGroup, */ ElInput, /* ElAutocomplete, */
    /* ElSelect, ElOption, ElOptionGroup, ElCheckboxGroup, ElCheckbox, */
} from 'element-plus'

import { $ } from 'vue/macros'
import { AgGridVue } from 'ag-grid-vue3'
import { Codemirror } from 'vue-codemirror'
import COMJS from '@/libs/common'
import Utils from '@/libs/utils'

import zhCn from 'element-plus/dist/locale/zh-cn.mjs'
import 'ag-grid-community/styles/ag-grid.css'
import 'ag-grid-community/styles/ag-theme-alpine.css'

// CodeMirror 6 引入
import { basicSetup } from "codemirror";
import { EditorView, ViewUpdate } from "@codemirror/view";
// import { EditorState } from '@codemirror/state'
import { sql, MySQL } from '@codemirror/lang-sql'
// import { keymap } from '@codemirror/view'
// import { sublimeKeymap } from '@replit/codemirror-vim'
// import { oneDark } from '@codemirror/theme-one-dark'

const commProps = defineProps({
    modelValue: { type: String, required: true },
    maintabid: { type: Number, default: 0 },
    maintabname: { type: String, default: '' },
    connention: { type: String, default: '' },
    database: { type: String, default: '' },
    datatable: { type: String, default: '' },
})

// const router = useRouter()
const { locale, t } = useI18n()
let elLocale = zhCn
let gridApi = $ref({})
let columnApi = $ref({})

let thatMainTabId = commProps.maintabid
let thatMainTabName = commProps.maintabname
let currConnetion = commProps.connention
let currDatabase = commProps.database
let currDataTable = commProps.datatable

let panelTabsCurr = $ref('0')

let currMode = $ref('')
let queryBody = $ref('')
let queryData = $ref([])
let currQueryTip = $ref('')
let currQueryTable = $ref('') // 当前查询返回的表名
let currColumnOrder = $ref([]) // 当前查询返回的列名顺序

let queryEditorContainerHeight = $ref('')

let historyBody = $ref('')
let ddlBody = $ref('')
let ddlColumn = $ref('')
let snippetData = $ref([])

let saveQueryDialogVisible = $ref(false)
let saveQueryTitle = $ref('')

let showTableStructureEditor = $ref(false)
let showTableIndexEditor = $ref(false)

let oldEditorLineCount = 0
let cmExtensionsQuery = [
    basicSetup,
    sql({ dialect: MySQL }),
    EditorView.updateListener.of((update) => {
        if (update.docChanged) {
            // 获取行数
            const lineCount = update.view.state.doc.lines;
            if (lineCount != oldEditorLineCount) {
                oldEditorLineCount = lineCount
                const editorElement = update.view.dom;
                if (editorElement.scrollHeight < 580) {
                    editorElement.style.height = 'auto';
                }
                if (editorElement.scrollHeight > 250) {
                    if (editorElement.scrollHeight > 600) {
                        editorElement.style.height = '600px';
                    } else {
                        if (editorElement.scrollHeight > 580 && editorElement.scrollHeight < 600) {
                            if (lineCount < 25) {
                                if (lineCount < 13) {
                                    editorElement.style.height = '250px';
                                } else {
                                    editorElement.style.height = 'auto';
                                }
                            } else {
                                editorElement.style.height = '600px';
                            }
                        } else {
                            editorElement.style.height = editorElement.scrollHeight + 'px';
                        }
                    }
                } else {
                    editorElement.style.height = '250px';
                }
            }
        }
    })
]

let cmExtensions = [
    basicSetup,
    sql({ dialect: MySQL }),
]

//### AgGrid相关
const gridOptions = $ref({
    headerHeight: 38,
    localeText: {
        selectAll: t('message.selectAll'),
        searchOoo: t('message.searchOoo'),
        blanks: t('message.blanks'),
        noMatches: t('message.noMatches'),
        
        noRowsToShow: t('message.noRowsToShow'),
    },
    /* getLocaleText: (key) => {
        // console.log(arguments)
        console.log(key)
        // 提供你自己的翻译映射
        let localeTexts = {
            // 这里可以根据需要添加更多的文本映射
            sorting: '排序',
            noRowsToShow: '没有要显示的行',
        }
        return localeTexts[key] || key.defaultValue
    }, */
})
const defaultColDef = $ref({
    sortable: true,
    filter: true,
    // flex: 1,
    // resizable: true,
})
let currTheaders = $ref([])
let currTheadersFlat = $ref([])

const excelStyles = $ref([
    {
        id: 'asString',
        dataType: 'String'
    },
])

// const treeIsLeaf = (node, data) => {
//     return !data.children
// }

const initData = () => {
}

const submitQuery = () => {
    if (!currDataTable) {
        ElMessage.warning('请先选择一个数据表')
        return
    }
    let fooStr = queryBody.trim()
    if (fooStr == '') {
        // ElMessage.closeAll()
        ElMessage.warning(t('message.pleaseInput'))
        return
    }

    let params = {
        connetion: currConnetion,
        database: currDatabase,
        query: queryBody,
    }

    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/execute',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()

        currTheadersFlat = []
        currTheaders = []
        currColumnOrder = []
        currQueryTip = ''

        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            currQueryTip = resp.msg
            return false
        }
        if (resp.data.hasOwnProperty('affected_rows')) {
            let message = '影响行数：'+resp.data['affected_rows']
            ElMessage.success(message)
            if (resp.data['messages'].length > 0) {
                message += '<br/>'+resp.data['messages'].join('<br/>')
            }
            currQueryTip = message
            return false
        }
        // console.log(resp)
        currColumnOrder = resp.data['column_order']
        currQueryTip = 'Row Count('+resp.data['rowcount']+')'
        currQueryTable = resp.data['table_name']
        currColumnOrder.forEach((kname) => {
            addTableAutoHeader(kname)
        })

        let dLists = resp.data['dlists']
        dLists.forEach((Qitem) => {
            Object.keys(Qitem).forEach((kname) => {
                if (Qitem[kname] === null) {
                    Qitem[kname] = 'NULL'
                }
            })
        })

        queryData = dLists
        
        agridAutoSizeAll(100)
        agridAutoSizeAll(1600)
        ElMessage.success(t('message.operateSuccess'))
        return false
    },
    error => {
        loadingInstance.close()
        return true
    })
}

const collapseQuery = () => {
    let queryEditorContainer = document.querySelector('.queryEditorContainer-'+thatMainTabId)
    let fooHeight = parseInt(window.getComputedStyle(queryEditorContainer).height)
    if (fooHeight > 100) {
        // 获取当前编辑器容器高度
        queryEditorContainerHeight = fooHeight
        // 动态减小高度至 0，使用过渡动画让效果更平滑
        queryEditorContainer.style.transition = 'height 0.3s ease'
        queryEditorContainer.style.height = queryEditorContainerHeight + 'px'
        queryEditorContainer.offsetWidth // 触发重绘
        queryEditorContainer.style.height = '0px'
        queryEditorContainer.style.overflow = 'hidden'
    } else {
        // 动态增加高度至 auto，使用过渡动画让效果更平滑
        queryEditorContainer.style.transition = 'height 0.3s ease'
        queryEditorContainer.style.height = '0px'
        queryEditorContainer.offsetWidth // 触发重绘
        queryEditorContainer.style.height = queryEditorContainerHeight + 'px'
        setTimeout(() => {
            queryEditorContainer.style.height = 'auto'
            queryEditorContainer.style.overflow = 'auto'
        }, 350)
    }
}

const showSaveQueryDialog = () => {
    if (panelTabsCurr !== '0') {
        return false
    }
    let fooStr = queryBody.trim()
    // console.log(currDataTable)
    if (fooStr == '') {
        ElMessage.warning(t('message.pleaseInput'))
        return
    }
    saveQueryTitle = ''
    // 匹配出fooStr第一行中的--后面的字符串
    let fooTitleArr = fooStr.match(/^--(.*)/)
    if (fooTitleArr && fooTitleArr.length > 0) {
        saveQueryTitle = fooTitleArr[1]
        saveQueryTitle = saveQueryTitle.trim()
    }
    saveQueryDialogVisible = true
}

const saveQuery = () => {
    let fooStr = queryBody.trim()
    if (fooStr == '') {
        ElMessage.warning(t('message.pleaseInput'))
        return
    }
    let params = {
        connetion: currConnetion,
        database: currDatabase,
        title: saveQueryTitle,
        query: queryBody,
    }
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/save-query',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        ElMessage.success(t('message.saveSuccess'))
        saveQueryDialogVisible = false
        return false
    },
    error => {
        loadingInstance.close()
        return true
    })
}

const showHistory = () => {
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/history',
    }, 'GET',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessageBox.alert(resp.msg, t('message.alertError'), {type: 'warning'})
            return false
        }
        historyBody = resp.data.join('\n')
        return false
    },
    error => {
        loadingInstance.close()
        return true
    })
}

const showDDL = () => {
    if (!currDataTable) {
        ElMessage.warning('请先选择一个数据表')
        return
    }
    ddlBody = ''
    ddlColumn = ''
    let params = {
        connetion: currConnetion,
        database: currDatabase,
        datatable: currDataTable,
    }
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/ddl',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        resp.data['column'].forEach((item, index) => {
            if (index == resp.data['column'].length - 1) {
                ddlColumn += '`'+item['field']+'` -- '+item['comment']+'\n'
            } else {
                ddlColumn += '`'+item['field']+'`, -- '+item['comment']+'\n'
            }
        })
        if (ddlColumn != '') {
            ddlColumn = 'SELECT \n'+ddlColumn+'FROM `'+currDataTable+'`\n\n'
        }
        ddlBody = resp.data['table_ddl'][0]['create_table']
        return false
    },
    error => {
        loadingInstance.close()
        return true
    })
}

const ddlBodyCopyThat = () => {
    // 实现复制到剪切板逻辑
    navigator.clipboard.writeText(ddlBody).then(() => {
        ElMessage.success('复制成功')
    }).catch(() => {
        ElMessage.error('复制失败')
    })
}

const ddlColumnCopyThat = () => {
    // 实现复制到剪切板逻辑
    navigator.clipboard.writeText(ddlColumn).then(() => {
        ElMessage.success('复制成功')
    }).catch(() => {
        ElMessage.error('复制失败')
    })
}

const showSnippets = () => {
    if (!currDataTable) {
        ElMessage.warning('请先选择一个数据表')
        return
    }
    snippetData = []
    let params = {
        connetion: currConnetion,
        database: currDatabase,
    }
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/snippets',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        snippetData = resp.data
        return false
    },
    error => {
        loadingInstance.close()
        return true
    })
}

const snippetDelete = (scopeItem) => {
    ElMessageBox.confirm('是否继续此操作?', '提示', {type: 'warning'}).then(() => {
        let params = {
            connetion: currConnetion,
            database: currDatabase,
            id: scopeItem.id,
        }
        COMJS.doAjax({
            url: $configSite.apicomm+'app.php/api/data-api/snippets-delete',
            data: params,
        }, 'POST',
        response => {
            let resp = response.data
            if (resp.status !== 0) {
                ElMessage.error(resp.msg)
                return false
            }
            showSnippets()
            return false
        },
        error => {
            return true
        })
    }).catch(() => {
        
    })
}

const snippetCopy = (scopeItem) => {
    // 实现复制到剪切板逻辑
    navigator.clipboard.writeText(scopeItem.content).then(() => {
        ElMessage.success('复制成功')
    }).catch(() => {
        ElMessage.error('复制失败')
    })
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

// 恢复表头排序
const restoreSortTableHeader = () => {
    // localStorage.removeItem('columndefs_'+locale.value+'_'+currServid)
    // currTheaders.sort((a, b) => currTheadersFlat.indexOf(a.headerName) - currTheadersFlat.indexOf(b.headerName))
}

const addTableAutoHeader = (kname) => {
    // BEGIN自动表头
    let exField = ['select-checkbox', 'pnum']
    let insertInd = 0
    if (kname == '' || exField.indexOf(kname) != -1 || currTheadersFlat.indexOf(kname) != -1) {
        return
    }
    insertInd = currTheaders.length
    // if (kname.toLowerCase() == 'id') {
    //     insertInd = 0
    // }

    // currTheadersFlat.push(kname)
    currTheadersFlat.splice(insertInd, 0, kname)
    currTheaders.splice(insertInd, 0, {
        headerName:kname,
        field:kname,
        // width: 100,
        maxWidth: 400,
        // suppressSizeToFit: true,
        editable: true,
        cellClass: 'asString',
        cellRenderer: (cell) => {
            // console.log(cell)
            return cell.value
        },
    })
    // console.log(currTheadersFlat)
    // customSortTableHeader()
}

//### AgGrid相关
const onGridReady = (agGrid) => {
    gridApi = agGrid.api
    columnApi = agGrid.columnApi
    // window.gridApi = gridApi
    // window.columnApi = columnApi
}

const onFirstDataRendered = () => {
    agridAutoSizeAll(100)
}

const onRowDataUpdated = () => {
    // agridAutoSizeAll(100)
    // agridAutoSizeAll(1600)
}

// 设置主键字段
const getRowId = (params) => {
    return params.data.id
}

const agridAutoSizeAll = (timeout) => {
    setTimeout(() => {
        const allColumnIds = [];
        columnApi.getColumns().forEach((column) => {
            if (['select-checkbox', 'pnum'].indexOf(column.getId()) == -1) {
                allColumnIds.push(column.getId())
            }
        })
        // console.log(allColumnIds)
        columnApi.autoSizeColumns(allColumnIds)
    }, timeout)
}

const keyboardEvent = (e) => {
    if (thatMainTabName != commProps.modelValue) {
        return
    }
    // console.log(thatMainTabName, commProps.modelValue)
    if (e.ctrlKey && e.key == 's') {
        e.preventDefault()
        showSaveQueryDialog()
        e.returnValue = false
        return false
    }
    if (e.ctrlKey && e.key == '\\') {
        e.preventDefault()
        collapseQuery()
        e.returnValue = false
        return false
    }
    if (e.altKey && e.key == 'Enter') {
        e.preventDefault()
        if (panelTabsCurr !== '0') {
            return false
        }
        submitQuery()
        e.returnValue = false
        return false
    }
}

onBeforeMount(() => {
    initData()
    initLanguage()
})

onMounted(() => {
    document.addEventListener('keydown', keyboardEvent)
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', keyboardEvent)
    // 清理数据
    gridApi = null
    columnApi = null
    
    queryBody = null
    queryData = null

    historyBody = null
    ddlBody = null
    ddlColumn = null
    snippetData = null

    cmExtensionsQuery = []
})
</script>