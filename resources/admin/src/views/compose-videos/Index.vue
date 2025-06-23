<template>
  <page-content>
    <space class="my-1">
      <search-form :fields="search"/>

      <!-- 下载按钮 -->
      <a-button 
        type="primary" 
        @click="handleDownload"
        :disabled="selectedVideos.length === 0"
        style="margin-left: 12px"
      >
        下载选中视频（{{ selectedVideos.length }}）
      </a-button>
    </space>

    <a-table
      row-key="id"
      :data-source="composeVideo"
      bordered
      :scroll="{ x: 600 }"
      :pagination="false"
    >
     <!-- 新增选择列 -->
      <a-table-column title="选择" :width="60">
        <template #header>
          <a-checkbox
            :checked="isAllSelected"
            @change="e => handleSelectAll(e)"
          />
        </template>
        <template #default="record">
          <a-checkbox
            :checked="selectedVideos.includes(record.id)"
            @change="checked => handleCheckboxChange(record.id, checked.target.checked)"
          />
        </template>
      </a-table-column>
      <a-table-column title="ID" data-index="id" :width="60"/>
      <a-table-column title="标题" data-index="title"/>
      <a-table-column title="模板规则" data-index="class_rules"/>
      <a-table-column title="原始素材" data-index="material_titles" :width="200"/>
      <a-table-column title="产品" data-index="product_id"  key="product_id">
        <template slot-scope="text">
            {{ getProductLabel(text) }}
        </template>
      </a-table-column>

      <a-table-column title="规格" data-index="product_format" key="product_format" :width="80">
          <template slot-scope="text">
                {{ getProductFormatLabel(text) }}
          </template>
      </a-table-column>
      <a-table-column title="横竖屏" data-index="screen_type" key="screen_type" :width="80">
      <template slot-scope="text">
            {{ getScreenTypeLabel(text) }}
        </template>
      </a-table-column>
      <a-table-column title="视频" key="url">
        <template #default="record">
          <div v-if="record.url" style="cursor: pointer" @click="handlePlayVideo(record.url)">
            <img
              :width="120"
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
      <a-table-column title="创建人" data-index="name" :width="80"/>
      <a-table-column title="添加时间" data-index="created_at" :width="120"/>
      <a-table-column title="操作" :width="100">
        <template #default="record">
          <space>
            <lz-popconfirm :confirm="destroyComposeVideo(record.id)">
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
  destroyComposeVideo,
  getComposeVideos,
} from '@/api/compose-videos'
import {
  getAdminActorList,
} from '@/api/admin-actors'
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
          field: 'product_id',
          label: '产品',
          type: 'select',  // 指定为下拉框类型
          options: [      // 定义下拉选项
            { id: 1, name: '舒筋健腰丸' },
            { id: 2, name: '清血八味片' },
            { id: 3, name: '咽康' }
          ]
        },
      ],
      selectedVideos: [],
      downloadVideos: [],
      composeVideo: [],
      page: null,
    }
  },
  // 添加生命周期钩子获取演员数据
  async created() {
    await this.fetchActorOptions()
  },

  computed: {
    // 新增计算属性判断全选状态
    isAllSelected() {
      const currentPageIds = this.composeVideo.map(item => item.id);
      return currentPageIds.length > 0 && 
             currentPageIds.every(id => this.selectedVideos.includes(id));
    }
  },
  methods: {
    // 新增全选处理方法
    handleSelectAll(e) {
      const currentPageIds = this.composeVideo.map(item => item.id);
      if (e.target.checked) {
        // 全选：添加当前页所有未选中的ID
        const newIds = currentPageIds.filter(
          id => !this.selectedVideos.includes(id)
        );
        this.selectedVideos = [...new Set([...this.selectedVideos, ...newIds])];
      } else {
        // 取消全选：移除当前页所有ID
        this.selectedVideos = this.selectedVideos.filter(
          id => !currentPageIds.includes(id)
        );
      }
    },
    // 修改原有复选框处理方法
    handleCheckboxChange(id, checked) {
      if (checked) {
        this.selectedVideos.push(id)
      } else {
        this.selectedVideos = this.selectedVideos.filter(item => item !== id)
      }
    },
    destroyComposeVideo(id) {
      return async () => {
        await destroyComposeVideo(id)
        this.composeVideo = removeWhile(this.composeVideo, (i) => i.id === id)
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
    getActorName(ids) {
      // 获取演员选项配置（包含id-name映射）
      const actorField = this.search.find(field => field.field === 'actor');
      const actorOptions = actorField ? actorField.options : [];
     
      // 处理空值情况
      if (!ids || !actorOptions.length) return '';
     
      // 标准化ID格式（处理单值/数组/字符串情况）
      const idList = Array.isArray(ids) ? [...new Set(ids)] : [ids];
      
      // 执行名称映射
      return idList
        .map(id => {
          const match = actorOptions.find(opt => opt.id === id);
          return match ? match.name : `未知演员(${id})`;
        })
        .join(', ');
    },
    getScreenTypeLabel(classValue) {
      const classMap = {
        1: '横屏',
        2: '竖屏'
      };
      return classMap[classValue] || classValue; // 找不到则显示原值
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

    // 新增复选框处理方法
    handleCheckboxChange(id, checked) {
      if (checked) {
        this.selectedVideos.push(id)
      } else {
        this.selectedVideos = this.selectedVideos.filter(item => item !== id)
      }
    },

    async handleDownload() {
      if (this.selectedVideos.length > 20){
          this.$message.warning('请选择最多20个视频进行下载');
          return;
      }

      try {

        
        const downloadVideos = this.selectedVideos
          .map(videoId => {
            const video = this.composeVideo.find(v => v.id === videoId);
            if (!video) return null;     
            if (!video.url) {
              console.warn(`视频 ${videoId}(${video.title}) 缺少下载地址`);
              return null;
            }
     
            return video;
          })
          .filter(Boolean);
        // 批量下载处理
        this.downloadVideos = downloadVideos; 

        for (let i = 0; i < this.downloadVideos.length; i++) {
          const video = this.downloadVideos[i];
          
          // 获取下载链接
          const downloadUrl = video.url;
          
          // 创建隐藏链接触发下载
          this.createDownloadLink(downloadUrl, 'test001');
          
          // 添加延迟避免浏览器并发限制
          await new Promise(resolve => setTimeout(resolve, 300));
        }

        this.$message.success(`已添加 ${this.selectedVideos.length} 个下载任务`);
      } catch (error) {
        this.$message.error(`下载失败: ${error.message}`);
      } finally {
        hideLoading && hideLoading();
      }
    },

    // 创建下载链接并触发点击
    createDownloadLink(url, fileName) {
      const link = document.createElement('a');
      link.href = url;
      link.download = fileName || 'video'; // 设置默认文件名
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },

  },
  watch: {
    $route: {
      async handler(newVal) {
        const { data: { data, meta } } = await getComposeVideos(newVal.query)
        this.composeVideo = data
        this.page = meta

        this.$scrollResolve()
      },
      immediate: true,
    },
  },
}
</script>
