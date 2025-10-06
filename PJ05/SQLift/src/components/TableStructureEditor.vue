<template>
    <div class="table-structure-editor">
        <div class="table-info-row">
            <el-row :gutter="20">
                <el-col :span="4">
                    <label>表名：</label>
                    <el-input v-model="tableStructure.tableName" size="small" style="width: 150px;"/>
                </el-col>
                <el-col :span="4">
                    <label>引擎：</label>
                    <el-select v-model="tableStructure.engine" size="small" style="width: 100px;">
                        <el-option label="InnoDB" value="InnoDB"/>
                        <el-option label="MyISAM" value="MyISAM"/>
                    </el-select>
                </el-col>
                <el-col :span="4">
                    <label>字符集：</label>
                    <el-select v-model="tableStructure.charset" size="small" style="width: 150px;">
                        <el-option label="utf8_general_ci" value="utf8_general_ci"/>
                        <el-option label="utf8mb4_general_ci" value="utf8mb4_general_ci"/>
                        <el-option label="latin1_swedish_ci" value="latin1_swedish_ci"/>
                    </el-select>
                </el-col>
                <el-col :span="4">
                    <label>表注释：</label>
                    <el-input v-model="tableStructure.tableComment" size="small" style="width: 150px;"/>
                </el-col>
                <el-col :span="4">
                    <!-- <el-button type="danger" size="small" @click="deleteTable">删除</el-button> -->
                    <el-button type="primary" size="small" @click="saveTableStructure">保存</el-button>
                    <el-button type="warning" size="small" @click="saveTableStructure(true)">预览</el-button>
                    <el-button size="small" @click="showTableStructure">刷新</el-button>
                </el-col>
            </el-row>
        </div>
        
        <div class="table-fields-editor">
            <el-table :data="tableStructureFields" :border="true" style="width: 100%">
                <el-table-column prop="name" label="字段名" width="150">
                    <template #default="scope">
                        <el-input v-model="scope.row.name" size="small" placeholder="字段名"/>
                    </template>
                </el-table-column>
                
                <el-table-column prop="type" label="类型" width="120">
                    <template #default="scope">
                        <select v-model="scope.row.type" @change="onFieldTypeChange(scope.row)" class="native-select">
                            <optgroup label="数字">
                                <option value="tinyint">tinyint</option>
                                <option value="smallint">smallint</option>
                                <option value="mediumint">mediumint</option>
                                <option value="int">int</option>
                                <option value="bigint">bigint</option>
                                <option value="decimal">decimal</option>
                                <option value="float">float</option>
                                <option value="double">double</option>
                            </optgroup>
                            <optgroup label="字符串">
                                <option value="char">char</option>
                                <option value="varchar">varchar</option>
                                <option value="tinytext">tinytext</option>
                                <option value="text">text</option>
                                <option value="mediumtext">mediumtext</option>
                                <option value="longtext">longtext</option>
                            </optgroup>
                            <optgroup label="日期时间">
                                <option value="datetime">datetime</option>
                                <option value="timestamp">timestamp</option>
                                <option value="date">date</option>
                                <option value="time">time</option>
                            </optgroup>
                        </select>
                    </template>
                </el-table-column>
                
                <el-table-column prop="length" label="长度" width="100">
                    <template #default="scope">
                        <el-input 
                            v-model="scope.row.length" 
                            size="small" 
                            :disabled="!(['varchar', 'char', 'int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double'].includes(scope.row.type))" 
                            placeholder="长度"
                        />
                    </template>
                </el-table-column>
                
                <el-table-column prop="options" label="选项" width="140">
                    <template #default="scope">
                        <select v-model="scope.row.options" class="native-select-full">
                            <option value="">无</option>
                            <option value="unsigned">unsigned</option>
                            <option value="zerofill">zerofill</option>
                            <option value="unsigned zerofill">unsigned zerofill</option>
                            <option value="binary">binary</option>
                        </select>
                    </template>
                </el-table-column>
                
                <el-table-column prop="allowNull" label="NULL" width="80" align="center">
                    <template #default="scope">
                        <el-checkbox v-model="scope.row.allowNull" size="small"/>
                    </template>
                </el-table-column>
                
                <el-table-column prop="defaultValue" label="默认值" width="150">
                    <template #default="scope">
                        <el-input v-model="scope.row.defaultValue" size="small" placeholder="默认值"/>
                    </template>
                </el-table-column>
                
                <el-table-column prop="comment" label="注释" width="220">
                    <template #default="scope">
                        <el-input v-model="scope.row.comment" size="small" placeholder="字段注释"/>
                    </template>
                </el-table-column>
                
                <el-table-column prop="autoIncrementIndex" label="自增" width="80" align="center">
                   <template #default="scope">
                        <el-radio v-model="tableStructure.autoIncrementIndex" :label="scope.$index" size="small" :disabled="!['int', 'tinyint', 'smallint', 'mediumint', 'bigint'].includes(scope.row.type)">&nbsp;</el-radio>
                    </template>
                </el-table-column>
                
                <el-table-column label="操作" width="200" align="center">
                    <template #default="scope">
                        <el-button type="success" size="small" @click="addField(scope.$index + 1)" title="添加">
                            <i class="fa fa-plus"></i>
                        </el-button>
                        <el-button type="info" size="small" @click="moveFieldUp(scope.$index)" :disabled="scope.$index === 0" title="上移">
                            <i class="fa fa-angle-up"></i>
                        </el-button>
                        <el-button type="info" size="small" @click="moveFieldDown(scope.$index)" :disabled="scope.$index === tableStructureFields.length - 1" title="下移">
                            <i class="fa fa-angle-down"></i>
                        </el-button>
                        <el-button type="danger" size="small" @click="removeField(scope.$index)" :disabled="tableStructureFields.length <= 1" title="删除">
                            <i class="fa fa-remove"></i>
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </div>
        
        <div class="table-options">
            <el-row>
                <el-col :span="8">
                    <label>自动增量：</label>
                    <el-input v-model="tableStructure.autoIncrementValue" size="small" style="width: 120px;"/>
                </el-col>
            </el-row>
        </div>
        
        <div class="table-actions">
            <!-- <el-button type="primary" @click="saveTableStructure">保存</el-button> -->
        </div>
    </div>

    <!-- SQL预览对话框 -->
    <el-dialog 
        v-model="sqlPreviewVisible" 
        title="SQL 预览"
        width="1000px"
    >
        <div class="sql-preview">
            <pre><code>{{ previewSql }}</code></pre>
        </div>
        <template #footer>
            <span class="dialog-footer">
                <el-button type="primary" size="small" @click="copyPreviewSql">复制</el-button>
                <el-button size="small" @click="sqlPreviewVisible = false">确定</el-button>
            </span>
        </template>
    </el-dialog>
    
</template>

<script setup>
import { onBeforeMount, onBeforeUnmount } from 'vue'
// import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
    ElConfigProvider,
    ElScrollbar, ElCol, ElRow,
    ElLoading, ElMessage, ElMessageBox, ElButton, /* ElButtonGroup, */ ElInput, /* ElAutocomplete, */
    ElSelect, ElOption, ElOptionGroup, ElCheckboxGroup, ElCheckbox,
    ElTable, ElTableColumn, ElRadio, ElDialog
} from 'element-plus'

import COMJS from '@/libs/common'

import zhCn from 'element-plus/dist/locale/zh-cn.mjs'

// CodeMirror 6 引入

const commProps = defineProps({
    connention: { type: String, default: '' },
    database: { type: String, default: '' },
    datatable: { type: String, default: '' }
})

// const router = useRouter()
const { locale, t } = useI18n()
let elLocale = zhCn

let currConnetion = commProps.connention
let currDatabase = commProps.database
let currDataTable = commProps.datatable
let sqlPreviewVisible = $ref(false)
let previewSql = $ref('')

// 表结构编辑相关数据
let tableStructure = $ref({
    tableName: '',
    tableComment: '',
    engine: 'InnoDB',
    charset: 'utf8_general_ci',
    autoIncrementIndex: -1, // -1表示无自动增量字段，其他值表示字段索引
    autoIncrementValue: '1'
})

// 表字段数据独立成变量
let tableStructureFields = $ref([
    {
        name: 'id',
        type: 'int',
        length: '10',
        options: 'unsigned',
        allowNull: false,
        defaultValue: '',
        comment: ''
    }
])

// 保存原始表结构数据，用于变化检测
let originalTableStructure = $ref(null)

const initData = () => {
    showTableStructure()
}

//### 表结构编辑相关方法

// 构建字段定义的辅助函数
const buildFieldDefinition = (field, index) => {
    let fieldDef = '`'+field.name+'` '+field.type.toUpperCase()
    
    // 添加长度
    if (field.length && fieldNeedsLength(field.type)) {
        fieldDef += '('+field.length+')'
    }
    
    // 添加选项（unsigned、zerofill等）
    if (field.options && field.options.trim() !== '') {
        fieldDef += ' '+field.options.toUpperCase()
    }
    
    // 添加 NULL/NOT NULL
    fieldDef += field.allowNull ? ' NULL' : ' NOT NULL'
    
    // 添加默认值
    if (field.defaultValue && field.defaultValue.trim() !== '') {
        if (['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext'].includes(field.type)) {
            fieldDef += " DEFAULT '"+field.defaultValue+"'"
        } else {
            fieldDef += ' DEFAULT '+field.defaultValue
        }
    }
    
    // 添加自动增量
    if (tableStructure.autoIncrementIndex === index) {
        fieldDef += ' AUTO_INCREMENT'
    }
    
    // 添加注释
    if (field.comment && field.comment.trim() !== '') {
        fieldDef += " COMMENT '"+field.comment+"'"
    }
    
    return fieldDef
}

// 检查字段是否有变化的辅助函数
const isFieldChanged = (currentField, originalField, currentIndex, originalIndex) => {
    // 检查字段属性是否有变化
    if (currentField.type !== originalField.type ||
        currentField.length !== originalField.length ||
        currentField.options !== originalField.options ||
        currentField.allowNull !== originalField.allowNull ||
        currentField.defaultValue !== originalField.defaultValue ||
        currentField.comment !== originalField.comment) {
        return true
    }
    
    // 检查自动增量设置是否有变化
    const currentIsAutoIncrement = tableStructure.autoIncrementIndex === currentIndex
    const originalIsAutoIncrement = originalTableStructure.autoIncrementIndex === originalIndex
    if (currentIsAutoIncrement !== originalIsAutoIncrement) {
        return true
    }
    
    return false
}

const showTableStructure = () => {
    if (!currDataTable) {
        ElMessage.warning('请先选择一个数据表')
        return
    }
    
    // 重置表结构数据
    tableStructure.tableName = currDataTable
    tableStructureFields = []
    
    let params = {
        connetion: currConnetion,
        database: currDatabase,
        datatable: currDataTable,
    }
    
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/table-structure',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        
        // 解析表结构数据
        if (resp.data && resp.data.fields) {
            // 创建新的字段数组，避免直接修改响应式数据
            let newFields = resp.data.fields.map(field => ({
                name: field.field || '',
                type: field.type || 'varchar',
                length: field.length || '',
                options: field.options || '',
                allowNull: field.null === 'YES',
                defaultValue: field.default || '',
                comment: field.comment || ''
            }))
            
            // 一次性更新响应式数据，减少重渲染次数
            tableStructureFields = newFields
            
            // 设置表信息
            tableStructure.engine = resp.data.engine || 'InnoDB'
            tableStructure.charset = resp.data.charset || 'utf8_general_ci'
            tableStructure.tableComment = resp.data.comment || ''
            tableStructure.autoIncrementValue = resp.data.auto_increment || 0
            
            // 查找自动增量字段
            tableStructure.autoIncrementIndex = -1
            resp.data.fields.forEach((field, index) => {
                if (field.extra && field.extra.includes('auto_increment')) {
                    tableStructure.autoIncrementIndex = index
                }
            })
            
            // 保存原始表结构数据，用于变化检测
            // 深拷贝避免引用问题
            originalTableStructure = JSON.parse(JSON.stringify({
                tableName: tableStructure.tableName,
                tableComment: tableStructure.tableComment,
                engine: tableStructure.engine,
                charset: tableStructure.charset,
                autoIncrementIndex: tableStructure.autoIncrementIndex,
                autoIncrementValue: tableStructure.autoIncrementValue,
                fields: tableStructureFields
            }))
        }
        return false
    },
    error => {
        loadingInstance.close()
        // 如果获取失败，使用默认结构
        tableStructureFields = [
            {
                name: 'id',
                type: 'int',
                length: '10',
                options: 'unsigned',
                allowNull: false,
                defaultValue: '',
                comment: ''
            }
        ]
        return true
    })
}

const fieldNeedsLength = (type) => {
    return ['varchar', 'char', 'int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double'].includes(type)
}

const onFieldTypeChange = (field) => {
    // 根据字段类型设置默认长度
    if (field.type === 'varchar' && !field.length) {
        field.length = '255'
    } else if (field.type === 'char' && !field.length) {
        field.length = '50'
    } else if (field.type === 'int' && !field.length) {
        field.length = '10'
    } else if (field.type === 'tinyint' && !field.length) {
        field.length = '1'
    } else if (field.type === 'smallint' && !field.length) {
        field.length = '5'
    } else if (field.type === 'mediumint' && !field.length) {
        field.length = '8'
    } else if (field.type === 'bigint' && !field.length) {
        field.length = '20'
    } else if (field.type === 'decimal' && !field.length) {
        field.length = '10,2'
    } else if (field.type === 'float' && !field.length) {
        field.length = '10,2'
    } else if (field.type === 'double' && !field.length) {
        field.length = '15,4'
    } else if (!fieldNeedsLength(field.type)) {
        field.length = ''
    }
    
    // 清除不适用的选项
    if (!['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double'].includes(field.type)) {
        // 对于非数字类型，清除 unsigned、zerofill 和 unsigned zerofill 选项
        if (field.options === 'unsigned' || field.options === 'zerofill' || field.options === 'unsigned zerofill') {
            field.options = ''
        }
    }
}

const addField = (index) => {
    const newField = {
        name: '',
        type: 'varchar',
        length: '255',
        options: '',
        allowNull: true,
        defaultValue: '',
        comment: ''
    }
    tableStructureFields.splice(index, 0, newField)
    
    // 更新自动增量字段索引
    if (tableStructure.autoIncrementIndex >= index) {
        tableStructure.autoIncrementIndex++
    }
}

const removeField = (index) => {
    if (tableStructureFields.length <= 1) {
        ElMessage.warning('至少需要保留一个字段')
        return
    }
    
    tableStructureFields.splice(index, 1)
    
    // 更新自动增量字段索引
    if (tableStructure.autoIncrementIndex === index) {
        tableStructure.autoIncrementIndex = -1
    } else if (tableStructure.autoIncrementIndex > index) {
        tableStructure.autoIncrementIndex--
    }
}

const moveFieldUp = (index) => {
    if (index > 0) {
        const field = tableStructureFields.splice(index, 1)[0]
        tableStructureFields.splice(index - 1, 0, field)
        
        // 更新自动增量字段索引
        if (tableStructure.autoIncrementIndex === index) {
            tableStructure.autoIncrementIndex = index - 1
        } else if (tableStructure.autoIncrementIndex === index - 1) {
            tableStructure.autoIncrementIndex = index
        }
    }
}

const moveFieldDown = (index) => {
    if (index < tableStructureFields.length - 1) {
        const field = tableStructureFields.splice(index, 1)[0]
        tableStructureFields.splice(index + 1, 0, field)
        
        // 更新自动增量字段索引
        if (tableStructure.autoIncrementIndex === index) {
            tableStructure.autoIncrementIndex = index + 1
        } else if (tableStructure.autoIncrementIndex === index + 1) {
            tableStructure.autoIncrementIndex = index
        }
    }
}

const saveTableStructure = (preview) => {
    // 验证表名
    if (!tableStructure.tableName.trim()) {
        ElMessage.warning('请输入表名')
        return
    }
    
    // 验证字段名
    for (let field of tableStructureFields) {
        if (!field.name.trim()) {
            ElMessage.warning('请为所有字段设置名称')
            return
        }
    }

    // 如果没有原始表结构数据，说明是新建表，不执行ALTER操作
    if (!originalTableStructure) {
        ElMessage.warning('请先加载表结构数据')
        return
    }

    // 构建ALTER TABLE语句
    let alterTableSql = 'ALTER TABLE `'+currDataTable+'`'
    let alterStatements = []
    
    // 处理表名修改
    if (tableStructure.tableName !== originalTableStructure.tableName) {
        alterStatements.push('RENAME TO `'+tableStructure.tableName+'`')
    }
    
    // 处理表注释修改
    if (tableStructure.tableComment !== originalTableStructure.tableComment) {
        if (tableStructure.tableComment) {
            alterStatements.push('COMMENT="'+tableStructure.tableComment+'"')
        } else {
            alterStatements.push('COMMENT=""')
        }
    }
    
    // 处理引擎修改
    if (tableStructure.engine !== originalTableStructure.engine) {
        alterStatements.push('ENGINE='+tableStructure.engine)
    }
    
    // 处理字符集修改
    if (tableStructure.charset !== originalTableStructure.charset) {
        alterStatements.push('DEFAULT CHARSET='+tableStructure.charset)
    }
    
    // 处理自动增量值修改
    if (tableStructure.autoIncrementValue !== originalTableStructure.autoIncrementValue) {
        alterStatements.push('AUTO_INCREMENT='+tableStructure.autoIncrementValue)
    }
    
    // 创建字段映射，方便比较
    const originalFieldMap = new Map()
    originalTableStructure.fields.forEach((field, index) => {
        originalFieldMap.set(field.name, { ...field, originalIndex: index })
    })
    
    const currentFieldMap = new Map()
    tableStructureFields.forEach((field, index) => {
        currentFieldMap.set(field.name, { ...field, currentIndex: index })
    })
    
    // 检查字段变化
    // 1. 检查修改和新增的字段
    tableStructureFields.forEach((currentField, currentIndex) => {
        const originalField = originalFieldMap.get(currentField.name)
        
        if (!originalField) {
            // 新增字段
            let fieldDef = buildFieldDefinition(currentField, currentIndex)
            alterStatements.push('ADD COLUMN '+fieldDef)
        } else {
            // 检查字段是否有变化
            if (isFieldChanged(currentField, originalField, currentIndex, originalField.originalIndex)) {
                let fieldDef = buildFieldDefinition(currentField, currentIndex)
                alterStatements.push('MODIFY COLUMN '+fieldDef)
            }
        }
    })
    
    // 2. 检查删除的字段
    originalTableStructure.fields.forEach(originalField => {
        if (!currentFieldMap.has(originalField.name)) {
            alterStatements.push('DROP COLUMN `'+originalField.name+'`')
        }
    })
    
    // 组合所有 ALTER 语句
    if (alterStatements.length > 0) {
        alterTableSql += ' ' + alterStatements.join(',\n')
    } else {
        if (preview) {
            ElMessage.warning('表结构没有变化')
            return
        }
        ElMessage.warning('表结构没有变化，无需保存')
        return
    }

    if (preview) {
        previewSql = alterTableSql
        sqlPreviewVisible = true
        return
    }
    
    // 构建完整的tableStructure对象用于API调用
    let completeTableStructure = {
        ...tableStructure,
        fields: tableStructureFields
    }
    
    let params = {
        connetion: currConnetion,
        database: currDatabase,
        originalTable: currDataTable,
        tableStructure: completeTableStructure,
        alterTableSql: alterTableSql,
    }
    
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/save-table-structure',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        ElMessage.success('表结构保存成功')
        return false
    },
    error => {
        loadingInstance.close()
        return true
    })
}

const deleteTable = () => {
    ElMessageBox.confirm('确定要删除表 "' + currDataTable + '" 吗？此操作不可撤销！', '警告', {
        type: 'warning',
        confirmButtonText: '确定删除',
        cancelButtonText: '取消'
    }).then(() => {
        let params = {
            connetion: currConnetion,
            database: currDatabase,
            datatable: currDataTable,
        }
        
        let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
        COMJS.doAjax({
            url: $configSite.apicomm+'app.php/api/data-api/database-delete',
            data: params,
        }, 'POST',
        response => {
            loadingInstance.close()
            let resp = response.data
            if (resp.status !== 0) {
                ElMessage.error(resp.msg)
                return false
            }
            ElMessage.success('表删除成功')
            return false
        },
        error => {
            loadingInstance.close()
            return true
        })
    }).catch(() => {
        // 用户取消删除
    })
}

// 复制预览SQL语句
const copyPreviewSql = () => {
    if (previewSql) {
        sqlPreviewVisible = false
        // 实现复制到剪切板逻辑
        navigator.clipboard.writeText(previewSql).then(() => {
            ElMessage.success('复制成功')
        }).catch(() => {
            ElMessage.error('复制失败')
        })
    }
}

//### END表结构编辑相关方法

const initLanguage = () => {
    let language = localStorage.getItem('language')
    if (language !== null) {
        locale.value = language
        if (language == 'en') {
            elLocale = null
        }
    }
}

onBeforeMount(() => {
    initData()
    initLanguage()
})

onBeforeUnmount(() => {
    tableStructure = null
    tableStructureFields = null
    originalTableStructure = null
})
</script>

<style scoped>
/* .table-structure-editor {
  contain: layout style;
}

.table-fields-editor {
  contain: layout;
} */


/* 减少表格重绘 */
/* :deep(.el-table) {
  contain: layout;
}

:deep(.el-table__body-wrapper) {
  contain: layout;
} */
 /* SQL预览样式 */
.sql-preview {
    background: #f5f5f5;
    border: 1px solid #e4e7ed;
    border-radius: 4px;
    padding: 15px;
    max-height: 800px;
    overflow-y: auto;
}

.sql-preview pre {
    margin: 0;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 14px;
    line-height: 1.5;
    color: #2c3e50;
}

.sql-preview code {
    background: none;
    padding: 0;
    color: inherit;
}
</style>