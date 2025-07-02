<template>
  <page-content>
    <space class="my-1">
      <search-form :fields="search"/>
      <!-- 添加导出按钮 -->
      <a-button type="primary" :loading="exportLoading" @click="handleExport">
        导出
      </a-button>
    </space>

    <a-table
      row-key="id"
      :data-source="adminMaterial"
      bordered
      :scroll="{ x: 600 }"
      :pagination="false"
      @change="handleTableChange"
    >
      <a-table-column title="ID" data-index="id" :width="60"/>
      <a-table-column title="标题" data-index="title"/>
      <a-table-column 
        title="分类" 
        data-index="class"
        key="class"
      >
        <template slot-scope="text">
          {{ getClassLabel(text) }}
        </template>
      </a-table-column>
      <a-table-column 
        title="子分类" 
        data-index="sub_class"
        key="sub_class"
      >
        <template slot-scope="text">
          {{ getSubClassLabel(text) }}
        </template>
      </a-table-column>
      <a-table-column title="视频" key="url" :width="120">
        <template #default="record">
          <div v-if="record.url" style="cursor: pointer" @click="handlePlayVideo(record.url)">
            <img
              :width="60"
              :src="record.video_cover_url || 'placeholder-image-url'"
            />
            <div style="margin-top: 4px; font-size: 12px">点击播放</div>
          </div>
          <span v-else>无视频</span>
        </template>
      </a-table-column>
      <a-table-column 
        title="演员" 
        data-index="actor_ids"
        key="actor_ids"
      >
        <template slot-scope="text">
          {{ getActorName(text) }}
        </template>
      </a-table-column>
      <a-table-column title="评分" key="score" :width="180">
        <template #default="record">
          <a-rate
            :value="record.score"
            :count="5"
            allow-half
            @change="value => handleRateChange(record.id, value)"
          />
        </template>
      </a-table-column>
      <a-table-column title="创建人" data-index="name" :width="180"/>
      <a-table-column 
        title="更新时间" 
        data-index="updated_at" 
        :width="180"
        :sorter="true"
        :sortOrder="activeSortField === 'updated_at' ? activeSortOrder : undefined"
        :sortDirections="['ascend', 'descend']"
      />
      <a-table-column title="操作" :width="100">
        <template #default="record">
          <space>
            <router-link :to="`/admin-materials/${record.id}/edit`">编辑</router-link>
            <lz-popconfirm :confirm="destroyAdminMaterial(record.id)">
              <a class="error-color" href="javascript:void(0);">删除</a>
            </lz-popconfirm>
          </space>
        </template>
      </a-table-column>
    </a-table>
    <lz-pagination :page="page"/>
  </page-content>
</template>

<script>
import LzPagination from '@c/LzPagination'
import LzPopconfirm from '@c/LzPopconfirm'
import PageContent from '@c/PageContent'
import SearchForm from '@c/SearchForm'
import Space from '@c/Space'
import {
  destroyAdminMaterial,
  getAdminMaterials,
  exportAdminMaterials,
  updateVideoScore,
} from '@/api/admin-materials'
import {
  getAdminActorList,
} from '@/api/admin-actors'
import { removeWhile } from '@/libs/utils'
import { saveAs } from 'file-saver'

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
          field: 'id',
          label: 'ID',
        },
        {
          field: 'title',
          label: '标题',
        },
        {
          field: 'actor',
          label: '演员',
          type: 'select',
          options: [] // 初始为空数组，后续通过接口填充
        },
        {
          field: 'screen_type',
          label: '横竖屏',
          type: 'select',  // 指定为下拉框类型
          options: [      // 定义下拉选项
            { id: 1, name: '横屏' },
            { id: 2, name: '竖屏' }
          ]
        },
        {
          field: 'class',
          label: '分类',
          type: 'select',  // 指定为下拉框类型
          options: [      // 定义下拉选项
            { id: 1, name: '营销内容' },
            { id: 2, name: '痛点/症状' },
            { id: 3, name: '产品背书' },
            { id: 4, name: '引导购买' }
          ]
        },
        {
          field: 'sub_class',
          label: '子分类',
          type: 'select',  // 指定为下拉框类型
          options: [      // 定义下拉选项
            { id: 11, name: 'A1-营销内容' },
            { id: 12, name: 'A2-价格营销' },
            { id: 14, name: 'A4-营销内容-合规' },
            { id: 15, name: 'A5-价格营销-合规' },
            { id: 16, name: 'A6-旧素材混剪' },
            { id: 18, name: 'A8-' },
            { id: 21, name: 'B1-症状代入' },
            { id: 22, name: 'B2-疾病科普' },
            { id: 23, name: 'B3-病理' },
            { id: 26, name: 'B6-旧素材混剪' },
            { id: 28, name: 'B8-' },
            { id: 31, name: 'C1-产品相关' },
            { id: 36, name: 'C6-旧素材混剪' },
            { id: 38, name: 'C8-' },
            { id: 41, name: 'D1-价格优惠' },
            { id: 42, name: 'D2-厂家直发' },
            { id: 43, name: 'D3-厂家活动' },
            { id: 44, name: 'D6-旧素材混剪' },
            { id: 48, name: 'D8-' },
          ]
        },
      ],
      adminMaterial: [],
      page: null,
      sortField: 'updated_at',      // 新增：默认排序字段
      sortOrder: 'asc',           // 新增：默认排序方向
      activeSortField: '',  // 当前排序字段
      activeSortOrder: '',     // 当前排序方向 ('ascend'|'descend')
      pagination: {                // 新增：分页控制
        current: 1,
        pageSize: 10
      },
      exportLoading: false, // 导出加载状态
    }
  },

  // 添加生命周期钩子获取演员数据
  async created() {
    await this.fetchActorOptions()
  },

  methods: {
    destroyAdminMaterial(id) {
      return async () => {
        await destroyAdminMaterial(id)
        this.adminMaterial = removeWhile(this.adminMaterial, (i) => i.id === id)
      }
    },

    handlePlayVideo(url) {
      this.$info({
        title: '视频播放',
        width: '450px', // 固定宽度
        bodyStyle: {
          height: '300px', // 固定内容区域高度
          padding: '0',
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center'
        },
        content: (
          <video 
            controls 
            autoplay 
            style={{ 
              width: '100%', 
              height: '100%',
              objectFit: 'contain' // 保持视频比例
            }}
          >
            <source src={url} type="video/mp4" />
          </video>
        ),
      });
    },

    getClassLabel(classValue) {
      const classMap = {
        1: '营销内容',
        2: '痛点/症状',
        3: '产品背书',
        4: '引导购买'
      };
      return classMap[classValue] || classValue; // 找不到则显示原值
    },

    getSubClassLabel(subClassValue) {
      const subClassMap = {
        11: 'A1-营销内容',
        12: 'A2-价格营销',
        14: 'A4-营销内容-合规',
        15: 'A5-价格营销-合规',
        16: 'A6-旧素材混剪',
        18: 'A8-',
        21: 'B1-症状代入',
        22: 'B2-疾病科普',
        23: 'B3-病理',
        26: 'B6-旧素材混剪',
        28: 'B8-',
        31: 'C1-产品相关',
        36: 'C6-旧素材混剪',
        38: 'C8-',
        41: 'D1-价格优惠',
        42: 'D2-厂家直发',
        43: 'D3-厂家活动',
        44: 'D6-旧素材混剪',
        48: 'D8-'
      };
      return subClassMap[subClassValue] || subClassValue; // 找不到则显示原值
    },

    getActorName(ids) {
      // 获取演员选项配置（包含id-name映射）
      const actorField = this.search.find(field => field.field === 'actor');
      const actorOptions = actorField ? actorField.options : [];
     
      // 处理空值情况
      if (!ids || !actorOptions.length) return '';
     
      // 标准化ID格式（处理单值/数组/字符串情况）
      const idList = Array.isArray(ids) ? ids : [ids];
      
      // 执行名称映射
      return idList
        .map(id => {
          const match = actorOptions.find(opt => opt.id === id);
          return match ? match.name : `未知演员(${id})`;
        })
        .join(', ');
    },

    handleTableChange(pagination, filters, sorter) {
      // 处理分页变化
      if (pagination) {
        this.pagination.current = pagination.current;
      }
      
      // 处理排序变化
      if (sorter && sorter.field) {
        this.activeSortField = sorter.field;
        this.activeSortOrder = sorter.order;
      } else {
        // 重置为默认排序
        this.activeSortField = 'updated_at';
        this.activeSortOrder = 'descend';
      }
      
      this.fetchData();
    },

    async fetchData() {
        const { path } = this.$route;
        let product_id = 1;

        if (path.endsWith('/qingxue')) {
          product_id = 2;
        } else if (path.endsWith('/yankang')) {
          product_id = 3;
        }
        const params = {
          ...this.$route.query,
          product_id: product_id,
          sort_field: this.activeSortField,
          sort_order: this.activeSortOrder === 'ascend' ? 'asc' : 'desc'
        };
        
        const { data } = await getAdminMaterials(params);
        this.adminMaterial = data.data;
        this.page = data.meta;
    },

    async fetchActorOptions() {
      try {
        const response = await getAdminActorList();
        // 找到actor字段的配置项
        const actorField = this.search.find(f => f.field === 'actor');
        
        if (actorField && Array.isArray(response.data)) {
          actorField.options = response.data.map(item => ({
            id: item.id,
            name: item.name,
          }));
        } else {
          console.warn('演员数据格式异常:', response.data);
          this.$message.error('演员数据格式异常');
        }
      } catch (error) {
        console.error('获取演员列表失败:', error);
        this.$message.error('获取演员列表失败');
      }
    },

    async handleRateChange(videoId, newScore) {
      try {
        // 这里调用评分接口（需要根据实际API调整）
        await updateVideoScore({id: videoId, score: newScore })
        
        // 更新本地评分显示
        const videoIndex = this.adminMaterial.findIndex(v => v.id === videoId)
        if (videoIndex > -1) {
          this.$set(this.adminMaterial, videoIndex, {
            ...this.adminMaterial[videoIndex],
            score: newScore
          })
        }
        
        this.$message.success('评分更新成功')
      } catch (error) {
        this.$message.error('评分更新失败')
      }
    },

    // 新增导出方法
    async handleExport() {
      this.exportLoading = true;
      try {
        const { path } = this.$route;
        let product_id = 1;

        if (path.endsWith('/qingxue')) {
          product_id = 2;
        } else if (path.endsWith('/yankang')) {
          product_id = 3;
        }
        const exportParams = {
          ...this.$route.query,
          product_id: product_id,
          sort_field: this.activeSortField,
          sort_order: this.activeSortOrder === 'ascend' ? 'asc' : 'desc'
        };

        const response = await exportAdminMaterials(exportParams);
        
        // 1. 检查响应类型
        const contentType = response.headers['content-type'] || '';
        
        // 情况A: 成功返回 Excel 文件
        if (contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') || 
            contentType.includes('application/octet-stream')) {
          
          // 获取文件名
          let fileName = '素材列表.xlsx';
          const contentDisposition = response.headers['content-disposition'] || '';
          
          if (contentDisposition) {
            // 处理 UTF-8 文件名
            const utf8Match = contentDisposition.match(/filename\*=UTF-8''([\w%\-\.]+)/i);
            if (utf8Match && utf8Match[1]) {
              fileName = decodeURIComponent(utf8Match[1]);
            } else {
              // 处理普通文件名
              const match = contentDisposition.match(/filename="?([^;"]+)"?/i);
              if (match && match[1]) {
                fileName = match[1];
              }
            }
          }
          
          // 使用 file-saver 保存文件
          const blob = new Blob([response.data], { 
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
          });
          
          saveAs(blob, fileName);
          this.$message.success('文件下载完成');
          
        } 
        // 情况B: 错误响应 (JSON 格式)
        else if (contentType.includes('application/json')) {
          const errorData = response.data;
          this.$message.error(`导出失败: ${errorData.message || errorData.error}`);
        }
        // 情况C: 未知响应类型
        else {
          console.error('未知响应类型:', contentType, response.data);
          this.$message.error('导出失败: 服务器返回未知格式');
        }
        
      } catch (error) {
        console.error('导出请求失败:', error);
        
        // 处理网络错误或请求失败
        if (error.response) {
          // 处理 HTTP 错误响应 (500 等)
          if (error.response.data instanceof Blob) {
            // 尝试解析 Blob 格式的错误响应
            const reader = new FileReader();
            reader.onload = () => {
              try {
                const errorData = JSON.parse(reader.result);
                this.$message.error(`导出失败: ${errorData.message || errorData.error}`);
              } catch (e) {
                this.$message.error('导出失败: ' + reader.result.substring(0, 100));
              }
            };
            reader.readAsText(error.response.data);
          } else if (typeof error.response.data === 'object') {
            this.$message.error(`导出失败: ${error.response.data.message || error.response.statusText}`);
          } else {
            this.$message.error(`导出失败: ${error.response.status} ${error.response.statusText}`);
          }
        } else {
          this.$message.error(`导出失败: ${error.message || '网络请求失败'}`);
        }
      } finally {
        this.exportLoading = false;
      }
    }
  },
  watch: {
    $route: {
      handler() {
        this.fetchData();
      },
      immediate: true,
      deep: true
    }
  },
}
</script>
