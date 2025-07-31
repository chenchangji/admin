<template>
  <page-content>
    <space class="my-1">
      <search-form :fields="search"/>
    </space>

    <a-table
      row-key="id"
      :data-source="adminTemplate"
      bordered
      :scroll="{ x: 600 }"
      :pagination="false"
    >
      <a-table-column title="ID" data-index="id" :width="60"/>
      <a-table-column title="模板名称" data-index="title"/>
      <a-table-column title="产品" data-index="product_id"  key="product_id">
        <template slot-scope="text">
            {{ getProductLabel(text) }}
        </template>
      </a-table-column>
      <a-table-column title="规格" data-index="product_format" key="product_format">
      <template slot-scope="text">
            {{ getProductFormatLabel(text) }}
        </template>
      </a-table-column>
      <a-table-column title="横竖屏" data-index="screen_type" key="screen_type">
      <template slot-scope="text">
            {{ getScreenTypeLabel(text) }}
        </template>
      </a-table-column>
      <a-table-column title="添加时间" data-index="created_at" :width="180"/>
      <a-table-column title="操作" :width="120" fixed="right">
        <template #default="record">
          <div class="vertical-actions">
            <a-button 
              type="link" 
              size="small"
              class="action-btn"
              @click="$router.push(`/admin-templates/${record.id}/edit`)"
            >
              <template #icon><EditOutlined /></template>
              编辑
            </a-button>
            <a-button 
              type="link" 
              size="small"
              class="action-btn"
              @click="showGenerateDialog(record.id)"
            >
              <template #icon><VideoCameraAddOutlined /></template>
              生成视频
            </a-button>
            <lz-popconfirm 
              :confirm="destroyAdminTemplate(record.id)"
              placement="left"
            >
              <a-button 
                type="link" 
                size="small"
                class="action-btn error-color"
              >
                <template #icon><DeleteOutlined /></template>
                删除
              </a-button>
            </lz-popconfirm>
          </div>
        </template>
      </a-table-column>
    </a-table>
    <lz-pagination :page="page"/>
    <a-modal 
      v-model:visible="generateDialogVisible" 
      title="生成视频"
      @ok="handleGenerate"
      @cancel="closeGenerateDialog"
    >
      <a-form-item label="生成数量">
        <a-input-number 
          v-model:value="generateCount" 
          :min="1"
          :max="50" 
           style="width: 100%"
        />
      </a-form-item>
    </a-modal>
  </page-content>
</template>

<script>
import LzPagination from '@c/LzPagination'
import LzPopconfirm from '@c/LzPopconfirm'
import PageContent from '@c/PageContent'
import SearchForm from '@c/SearchForm'
import Space from '@c/Space'
import {
  destroyAdminTemplate,
  getAdminTemplates,
  generateTemplateVideo,

} from '@/api/admin-templates'
import { removeWhile } from '@/libs/utils'

export default {
  name: 'Index',
  scroll: true,
  components: {
    LzPopconfirm,
    PageContent,
    LzPagination,
    Space,
    SearchForm,
  },
  data() {
    return {
      search: [
        {
          field: 'title',
          label: '模板名称',
        },
      ],
      adminTemplate: [],
      page: null,
      generateDialogVisible: false,
      generateCount: null,
      selectedTemplateId: null,
    }
  },
  methods: {
    destroyAdminTemplate(id) {
      return async () => {
        await destroyAdminTemplate(id)
        this.adminTemplate = removeWhile(this.adminTemplate, (i) => i.id === id)
      }
    },
    async handleGenerate() {
      try {
        // 调用生成视频接口，需要自行实现或替换为实际API
        await generateTemplateVideo({
          template_id: this.selectedTemplateId,
          count: this.generateCount
        })
        this.$message.success('视频生成任务已提交')
        this.closeGenerateDialog()
      } catch (error) {
        this.$message.error('符合条件素材不足，请添加素材或确认规则！')
        console.error('生成视频失败:', error)
      }
    },
    getProductLabel(classValue) {
      const classMap = {
        1: '舒筋健腰丸',
        2: '清血八味片',
        3: '咽康'
      };
      return classMap[classValue] || classValue; // 找不到则显示原值
    },
    getProductFormatLabel(classValue) {
      const classMap = {
        1: '拆零',
        2: '大盒',
        3: '24片',
        4: '120片',
        5: '18片',
        6: '40片',
      };
      return classMap[classValue] || classValue; // 找不到则显示原值
    },
    getScreenTypeLabel(classValue) {
      const classMap = {
        1: '横屏',
        2: '竖屏'
      };
      return classMap[classValue] || classValue; // 找不到则显示原值
    },

    // 新增方法
    showGenerateDialog(templateId) {
      this.selectedTemplateId = templateId
      this.generateDialogVisible = true
      this.generateCount = null // 重置数量
    },
    closeGenerateDialog() {
      this.generateDialogVisible = false
    },
  },
  watch: {
    $route: {
      async handler(newVal) {
        const { data: { data, meta } } = await getAdminTemplates(newVal.query)
        this.adminTemplate = data
        this.page = meta

        this.$scrollResolve()
      },
      immediate: true,
    },
  },
}
</script>
