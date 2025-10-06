<template>
    <div class="table-index-editor">
        <!-- 索引管理标题和操作 -->
        <div class="index-header">
            <el-row :gutter="20">
                <el-col :span="12">
                    <el-button type="primary" size="small" @click="showAddIndexDialog">添加索引</el-button>&nbsp;
                    <el-button size="small" @click="refreshIndexes">刷新</el-button>
                </el-col>
            </el-row>
        </div>
        
        <!-- 索引列表 -->
        <div class="index-list">
            <el-table :data="indexList" :border="true" style="width: 100%" size="small">
                <el-table-column prop="operation" label="操作" width="120" align="center">
                    <template #default="scope">
                        <el-button 
                            type="warning" 
                            size="small" 
                            @click="editIndex(scope.row)" 
                            title="编辑"
                        >
                            <i class="fa fa-edit"></i>
                        </el-button>
                        <el-button 
                            type="danger" 
                            size="small" 
                            @click="deleteIndex(scope.row)" 
                            title="删除"
                            :disabled="scope.row.keyName === 'PRIMARY'"
                        >
                            <i class="fa fa-trash"></i>
                        </el-button>
                    </template>
                </el-table-column>
                
                <el-table-column prop="keyName" label="键名" width="180">
                    <template #default="scope">
                        <strong v-if="scope.row.keyName === 'PRIMARY'" class="primary-key">
                            {{ scope.row.keyName }}
                        </strong>
                        <span v-else>{{ scope.row.keyName }}</span>
                    </template>
                </el-table-column>
                
                <el-table-column prop="indexType" label="类型" width="100" align="center">
                    <template #default="scope">
                        {{ getIndexTypeLabel(scope.row.indexType) }}
                    </template>
                </el-table-column>
                
                <el-table-column prop="unique" label="唯一" width="80" align="center">
                    <template #default="scope">
                        {{ scope.row.unique ? '是' : '否' }}
                    </template>
                </el-table-column>
                
                <el-table-column prop="packed" label="紧凑" width="80" align="center">
                    <template #default="scope">
                        {{ scope.row.packed ? '是' : '否' }}
                    </template>
                </el-table-column>
                
                <el-table-column prop="fields" label="字段" width="200">
                    <template #default="scope">
                        <span v-for="(field, index) in scope.row.fields" :key="index">
                            {{ field.fieldName }}<span v-if="field.fieldSize">({{ field.fieldSize }})</span><span v-if="index < scope.row.fields.length - 1">, </span>
                        </span>
                    </template>
                </el-table-column>
                
                <el-table-column prop="cardinality" label="基数" width="100" align="center">
                    <template #default="scope">
                        {{ scope.row.cardinality || '-' }}
                    </template>
                </el-table-column>
                
                <el-table-column prop="collation" label="排序规则" width="100" align="center">
                    <template #default="scope">
                        <span v-for="(field, index) in scope.row.fields" :key="index">
                            {{ field.collation || 'A' }}<span v-if="index < scope.row.fields.length - 1">, </span>
                        </span>
                    </template>
                </el-table-column>
                
                <el-table-column prop="allowNull" label="空" width="80" align="center">
                    <template #default="scope">
                        {{ scope.row.allowNull ? '是' : '否' }}
                    </template>
                </el-table-column>
                
                <el-table-column prop="comment" label="注释" min-width="150">
                    <template #default="scope">
                        {{ scope.row.comment || '-' }}
                    </template>
                </el-table-column>
            </el-table>
        </div>
        
        <!-- 编辑索引对话框 -->
        <el-dialog
            v-model="indexDialogVisible"
            :title="isEditMode ? '编辑索引' : '添加索引'"
            width="600px"
            :close-on-click-modal="false"
        >
            <div class="index-form" v-if="currentIndex !== null ">
                <!-- 索引名称 -->
                <el-row :gutter="20" class="form-row">
                    <el-col :span="6">
                        <label class="form-label">索引名称：</label>
                    </el-col>
                    <el-col :span="18">
                        <el-input 
                            v-model="currentIndex.keyName" 
                            placeholder="请输入索引名称"
                            :disabled="isEditMode && currentIndex.keyName === 'PRIMARY'"
                        />
                    </el-col>
                </el-row>
                
                <!-- 索引类型 -->
                <el-row :gutter="20" class="form-row">
                    <el-col :span="6">
                        <label class="form-label">索引类型：</label>
                    </el-col>
                    <el-col :span="18">
                        <el-select 
                            v-model="currentIndex.indexType" 
                            style="width: 100%"
                            :disabled="isEditMode && currentIndex.keyName === 'PRIMARY'"
                        >
                            <el-option label="PRIMARY" value="PRIMARY" />
                            <el-option label="INDEX" value="INDEX" />
                            <el-option label="UNIQUE" value="UNIQUE" />
                            <el-option label="FULLTEXT" value="FULLTEXT" />
                        </el-select>
                    </el-col>
                </el-row>
                
                <!-- 索引方法 -->
                <el-row :gutter="20" class="form-row">
                    <el-col :span="6">
                        <label class="form-label">索引方法：</label>
                    </el-col>
                    <el-col :span="18">
                        <el-select 
                            v-model="currentIndex.indexMethod" 
                            style="width: 100%"
                        >
                            <el-option label="BTREE" value="BTREE" />
                            <el-option label="HASH" value="HASH" />
                        </el-select>
                    </el-col>
                </el-row>
                
                <!-- 索引注释 -->
                <el-row :gutter="20" class="form-row">
                    <el-col :span="6">
                        <label class="form-label">索引注释：</label>
                    </el-col>
                    <el-col :span="18">
                        <el-input 
                            v-model="currentIndex.comment" 
                            placeholder="请输入索引注释"
                            maxlength="255"
                            show-word-limit
                        />
                    </el-col>
                </el-row>
                
                
                <!-- 索引字段选择 -->
                <div class="index-fields-section">
                    <div class="fields-header">
                        <el-row>
                            <el-col :span="12">字段</el-col>
                            <el-col :span="6">大小</el-col>
                            <el-col :span="6">操作</el-col>
                        </el-row>
                    </div>
                    
                    <div class="field-rows">
                        <div v-for="(field, index) in currentIndex.fields" :key="index" class="field-row">
                            <el-row :gutter="10">
                                <el-col :span="12">
                                    <el-select 
                                        v-model="field.fieldName" 
                                        size="small" 
                                        style="width: 100%"
                                        placeholder="请选择字段"
                                    >
                                        <el-option 
                                            v-for="tableField in availableFields" 
                                            :key="tableField.name" 
                                            :label="tableField.name + ' [' + tableField.type + (tableField.length ? '(' + tableField.length + ')' : '') + ']'"
                                            :value="tableField.name"
                                        />
                                    </el-select>
                                </el-col>
                                <el-col :span="6">
                                    <el-input 
                                        v-model="field.fieldSize" 
                                        size="small" 
                                        placeholder=""
                                    />
                                </el-col>
                                <el-col :span="6" class="field-operations">
                                    <el-button 
                                        type="info" 
                                        size="small" 
                                        @click="moveFieldUp(index)"
                                        :disabled="index === 0"
                                        title="上移"
                                    >
                                        <i class="fa fa-angle-up"></i>
                                    </el-button>
                                    <el-button 
                                        type="info" 
                                        size="small" 
                                        @click="moveFieldDown(index)"
                                        :disabled="index === currentIndex.fields.length - 1"
                                        title="下移"
                                    >
                                        <i class="fa fa-angle-down"></i>
                                    </el-button>
                                    <el-button 
                                        type="danger" 
                                        size="small" 
                                        @click="removeIndexField(index)"
                                        :disabled="currentIndex.fields.length <= 1"
                                        title="删除"
                                    >
                                        <i class="fa fa-remove"></i>
                                    </el-button>
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                    
                    <!-- 添加字段按钮 -->
                    <div class="add-field-btn">
                        <el-button size="small" @click="addIndexField">
                            添加索引字段
                        </el-button>
                    </div>
                </div>
            </div>
            
            <template #footer>
                <span class="dialog-footer">
                    <el-button size="small" @click="previewIndexSql">预览 SQL 语句</el-button>
                    <el-button type="primary" size="small" @click="saveIndex">执行</el-button>
                    <el-button size="small" @click="indexDialogVisible = false">取消</el-button>
                </span>
            </template>
        </el-dialog>
        
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
    </div>
</template>

<script setup>
import { onBeforeMount, onBeforeUnmount } from 'vue'
import {
    ElConfigProvider,
    ElScrollbar, ElCol, ElRow,
    ElLoading, ElMessage, ElMessageBox, ElButton, ElInput,
    ElSelect, ElOption, ElOptionGroup, ElCheckboxGroup, ElCheckbox,
    ElTable, ElTableColumn, ElRadio, ElDialog
} from 'element-plus'

import COMJS from '@/libs/common'

const commProps = defineProps({
    connention: { type: String, default: '' },
    database: { type: String, default: '' },
    datatable: { type: String, default: '' }
})

let currConnetion = commProps.connention
let currDatabase = commProps.database
let currDataTable = commProps.datatable

// 索引管理相关数据
let indexList = $ref([])
let availableFields = $ref([])
let indexDialogVisible = $ref(false)
let sqlPreviewVisible = $ref(false)
let isEditMode = $ref(false)
let previewSql = $ref('')

// 当前编辑的索引
let currentIndex = $ref({
    keyName: '',
    indexType: 'INDEX',
    indexMethod: 'BTREE',
    unique: false,
    packed: false,
    fields: [{
        fieldName: '',
        fieldSize: '',
        collation: 'A'
    }],
    cardinality: 0,
    allowNull: false,
    comment: ''
})

// 原始索引数据，用于编辑模式下的比较
let originalIndexData = $ref(null)

const initData = () => {
    loadTableIndexes()
    loadTableFields()
}

const refreshIndexes = () => {
    loadTableIndexes()
    loadTableFields()
}

// 加载表索引信息
const loadTableIndexes = () => {
    if (!currDataTable) {
        ElMessage.warning('请先选择一个数据表')
        return
    }
    
    let params = {
        connetion: currConnetion,
        database: currDatabase,
        datatable: currDataTable,
    }
    
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/table-indexes',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        
        // 解析索引数据
        if (resp.data && resp.data.indexes) {
            let indexesData = resp.data.indexes.map((item) => {
                let fooFields = []
                item.fields.forEach((field) => {
                    fooFields.push({
                        fieldName: field.field_name,
                        fieldSize: field.field_size,
                        collation: field.collation
                    })
                })
                return {
                    keyName: item.key_name || '',
                    indexType: getIndexType(item),
                    indexMethod: item.index_method || 'BTREE',
                    unique: item.unique === '0',
                    packed: item.packed === 'YES',
                    fields: fooFields,
                    cardinality: parseInt(item.cardinality) || 0,
                    allowNull: item.allow_null === 'YES',
                    comment: item.comment || ''
                }
            })
            indexList = indexesData
            
            // 合并相同索引名的字段
            // mergeIndexFields()
        }
        return false
    },
    error => {
        loadingInstance.close()
        // 如果获取失败，使用默认索引结构
        indexList = []
        return true
    })
}

// 加载表字段信息
const loadTableFields = () => {
    if (!currDataTable) {
        return
    }
    
    let params = {
        connetion: currConnetion,
        database: currDatabase,
        datatable: currDataTable,
    }
    
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/table-structure',
        data: params,
    }, 'POST',
    response => {
        let resp = response.data
        if (resp.status !== 0) {
            return false
        }
        
        if (resp.data && resp.data.fields) {
            availableFields = resp.data.fields.map(field => ({
                name: field.field || '',
                type: field.type || '',
                length: field.length || ''
            }))
        }
        return false
    },
    error => {
        availableFields = []
        return true
    })
}

// 合并相同索引名的字段
const mergeIndexFields = () => {
    const indexMap = new Map()
    
    indexList.forEach(index => {
        if (indexMap.has(index.keyName)) {
            // 合并字段
            indexMap.get(index.keyName).fields.push(...index.fields)
        } else {
            indexMap.set(index.keyName, {
                ...index,
                fields: [...index.fields] // 创建新数组以避免引用问题
            })
        }
    })
    
    indexList = Array.from(indexMap.values())
}

// 获取索引类型
const getIndexType = (index) => {
    if (index.key_name === 'PRIMARY') {
        return 'PRIMARY'
    } else if (index.unique === '0') {
        return 'UNIQUE'
    } else if (index.index_type === 'FULLTEXT') {
        return 'FULLTEXT'
    } else {
        return 'INDEX'
    }
}

// 获取索引类型显示标签
const getIndexTypeLabel = (type) => {
    switch (type) {
        case 'PRIMARY':
            return 'PRIMARY'
        case 'UNIQUE':
            return 'UNIQUE'
        case 'FULLTEXT':
            return 'FULLTEXT'
        case 'SPATIAL':
            return 'SPATIAL'
        default:
            return 'INDEX'
    }
}

// 显示添加索引对话框
const showAddIndexDialog = () => {
    isEditMode = false
    currentIndex = {
        keyName: '',
        indexType: 'INDEX',
        indexMethod: 'BTREE',
        unique: false,
        packed: false,
        fields: [{
            fieldName: '',
            fieldSize: '',
            collation: 'A'
        }],
        cardinality: 0,
        allowNull: false,
        comment: ''
    }
    indexDialogVisible = true
}

// 编辑索引
const editIndex = (index) => {
    isEditMode = true
    originalIndexData = JSON.parse(JSON.stringify(index))
    currentIndex = JSON.parse(JSON.stringify(index))
    indexDialogVisible = true
}

// 删除索引
const deleteIndex = (index) => {
    if (index.keyName === 'PRIMARY') {
        ElMessage.warning('不能删除主键索引')
        return
    }
    
    ElMessageBox.confirm(
        '确定要删除索引 "' + index.keyName + '" 吗？',
        '确认删除',
        {
            type: 'warning',
            confirmButtonText: '是',
            cancelButtonText: '否'
        }
    ).then(() => {
        executeDropIndex(index)
    }).catch(() => {
        // 用户取消删除
    })
}

// 执行删除索引
const executeDropIndex = (index) => {
    
    let dropSql = 'ALTER TABLE `' + currDataTable + '` DROP INDEX `' + index.keyName + '`'
    
    let params = {
        connetion: currConnetion,
        database: currDatabase,
        datatable: currDataTable,
        sql: dropSql
    }
    
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/execute-index-sql',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        ElMessage.success('索引删除成功')
        loadTableIndexes() // 重新加载索引列表
        return false
    },
    error => {
        loadingInstance.close()
        return true
    })
}

// 添加索引字段
const addIndexField = () => {
    currentIndex.fields.push({
        fieldName: '',
        fieldSize: '',
        collation: 'A'
    })
}

// 移除索引字段
const removeIndexField = (index) => {
    if (currentIndex.fields.length > 1) {
        currentIndex.fields.splice(index, 1)
    }
}

// 上移字段
const moveFieldUp = (index) => {
    if (index > 0) {
        const field = currentIndex.fields.splice(index, 1)[0]
        currentIndex.fields.splice(index - 1, 0, field)
    }
}

// 下移字段
const moveFieldDown = (index) => {
    if (index < currentIndex.fields.length - 1) {
        const field = currentIndex.fields.splice(index, 1)[0]
        currentIndex.fields.splice(index + 1, 0, field)
    }
}

// 预览SQL语句
const previewIndexSql = () => {
    const alterTableSql = generateIndexSql()
    if (alterTableSql) {
        previewSql = alterTableSql
        sqlPreviewVisible = true
    }
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

// 生成索引SQL语句
const generateIndexSql = () => {
    // 验证索引名称
    if (!currentIndex.keyName.trim()) {
        return null // 不显示错误消息，只在保存时验证
    }
    
    // 验证索引字段
    const validFields = currentIndex.fields.filter(field => field.fieldName.trim() !== '')
    if (validFields.length === 0) {
        return null // 不显示错误消息，只在保存时验证
    }
    
    // 检查索引名称是否已存在（新增模式下）
    if (!isEditMode && indexList.some(index => index.keyName === currentIndex.keyName)) {
        return null // 不显示错误消息，只在保存时验证
    }
    
    let alterSql = ''
    
    if (isEditMode) {
        // 编辑模式：先删除再创建
        if (originalIndexData && originalIndexData.keyName !== 'PRIMARY') {
            alterSql = 'ALTER TABLE `' + (currDataTable) + '` DROP INDEX `' + originalIndexData.keyName + '`;\n';
        }
    }
    
    // 生成创建索引语句
    const tableName = currDataTable
    
    if (currentIndex.indexType === 'PRIMARY') {
        alterSql += 'ALTER TABLE `' + tableName + '` ADD PRIMARY KEY ('
    } else if (currentIndex.indexType === 'UNIQUE') {
        alterSql += 'ALTER TABLE `' + tableName + '` ADD UNIQUE KEY `' + currentIndex.keyName + '` ('
    } else if (currentIndex.indexType === 'FULLTEXT') {
        alterSql += 'ALTER TABLE `' + tableName + '` ADD FULLTEXT KEY `' + currentIndex.keyName + '` ('
    } else {
        alterSql += 'ALTER TABLE `' + tableName + '` ADD KEY `' + currentIndex.keyName + '` ('
    }
    
    // 添加字段
    const fieldParts = validFields.map(field => {
        let fieldPart = '`' + field.fieldName + '`'
        if (field.fieldSize && field.fieldSize.trim() !== '') {
            fieldPart += '(' + field.fieldSize + ')'
        }
        return fieldPart
    })
    
    alterSql += fieldParts.join(', ') + ')'
    
    // 添加索引方法
    if (currentIndex.indexMethod && currentIndex.indexMethod !== 'BTREE') {
        alterSql += ' USING ' + currentIndex.indexMethod
    }
    
    // 添加索引注释
    if (currentIndex.comment && currentIndex.comment.trim() !== '') {
        alterSql += " COMMENT '" + currentIndex.comment + "'"
    }
    
    return alterSql
}

// 保存索引
const saveIndex = () => {
    // 验证索引名称
    if (!currentIndex.keyName.trim()) {
        ElMessage.warning('请输入索引名称')
        return
    }
    
    // 验证索引字段
    const validFields = currentIndex.fields.filter(field => field.fieldName.trim() !== '')
    if (validFields.length === 0) {
        ElMessage.warning('至少需要一个字段')
        return
    }
    
    // 检查索引名称是否已存在（新增模式下）
    if (!isEditMode && indexList.some(index => index.keyName === currentIndex.keyName)) {
        ElMessage.warning('索引名称已存在')
        return
    }
    
    const alterTableSql = generateIndexSql()
    if (!alterTableSql) {
        return
    }
    
    let params = {
        connetion: currConnetion,
        database: currDatabase,
        datatable: currDataTable,
        alterTableSql: alterTableSql
    }
    
    let loadingInstance = ElLoading.service({background: 'rgb(255 255 255 / 0)'})
    COMJS.doAjax({
        url: $configSite.apicomm+'app.php/api/data-api/execute-index-sql',
        data: params,
    }, 'POST',
    response => {
        loadingInstance.close()
        let resp = response.data
        if (resp.status !== 0) {
            ElMessage.error(resp.msg)
            return false
        }
        ElMessage.success('索引操作成功')
        indexDialogVisible = false
        loadTableIndexes() // 重新加载索引列表
        return false
    },
    error => {
        loadingInstance.close()
        return true
    })
}

onBeforeMount(() => {
    initData()
})

onBeforeUnmount(() => {
    indexList = null
    availableFields = null
    currentIndex = null
    originalIndexData = null
})
</script>

<style scoped>
.table-index-editor {
    background: #fff;
}

.index-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e4e7ed;
}

.index-header h3 {
    margin: 0;
    color: #303133;
    font-size: 18px;
    font-weight: 500;
}

.text-right {
    text-align: right;
}

.index-list {
    margin-bottom: 20px;
}

.primary-key {
    color: #e6a23c;
    font-weight: bold;
}

/* 索引编辑对话框样式 */
.index-form {
    padding: 10px 0;
}

.form-row {
    margin-bottom: 15px;
    align-items: center;
}

.form-label {
    font-weight: 500;
    color: #606266;
    display: inline-block;
    text-align: right;
    padding-right: 10px;
}

.advanced-title {
    margin: 20px 0 15px 0;
    padding: 8px 0;
    border-bottom: 1px solid #e4e7ed;
    color: #409eff;
    font-weight: 500;
}

.advanced-title i {
    margin-right: 5px;
}

/* 索引字段选择区域 */
.index-fields-section {
    border: 1px solid #e4e7ed;
    border-radius: 4px;
    padding: 10px;
    background: #fafafa;
}

.fields-header {
    margin-bottom: 10px;
    font-weight: 500;
    color: #606266;
    border-bottom: 1px solid #e4e7ed;
    padding-bottom: 8px;
}

.field-rows {
    max-height: 300px;
    overflow-y: auto;
}

.field-row {
    margin-bottom: 10px;
    padding: 8px;
    background: #fff;
    border: 1px solid #e4e7ed;
    border-radius: 4px;
}

.field-operations {
    display: flex;
    gap: 2px;
    align-items: center;
    justify-content: flex-start;
}

.field-operations .el-button {
    min-width: 28px;
    padding: 5px 8px;
}

.add-field-btn {
    margin-top: 10px;
    text-align: center;
}

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

/* 表格样式优化 */
:deep(.el-table) {
    font-size: 14px;
}

:deep(.el-table .cell) {
    padding: 8px 10px;
}

:deep(.el-table th) {
    background-color: #f5f7fa;
    font-weight: 500;
}

:deep(.el-dialog__body) {
    padding: 20px;
}

:deep(.el-dialog__footer) {
    padding: 10px 20px 20px;
    text-align: right;
}

/* 按钮样式 */
:deep(.el-button--small) {
    padding: 5px 10px;
    font-size: 12px;
}
</style>