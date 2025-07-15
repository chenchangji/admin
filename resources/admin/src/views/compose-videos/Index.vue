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
      <a-table-column title="原始素材" data-index="material_titles" :width="200"/>
      <!-- <a-table-column title="产品" data-index="product_id"  key="product_id">
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
      -->
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
        :width="200"
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

      <a-table-column title="下载次数" data-index="download_count" :width="80"/>

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
import { Rate } from 'ant-design-vue'
import {
  destroyComposeVideo,
  getComposeVideos,
  downloadLog,
  updateVideoScore,
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
    ARate: Rate,
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
      // 1. 数量校验
      if (this.selectedVideos.length > 20) {
        this.$message.warning('请选择最多20个视频进行下载');
        return;
      }

      try {
        // 2. 预处理有效视频
        const validVideos = this.selectedVideos
          .map(videoId => this.composeVideo.find(v => v.id === videoId))
          .filter(video => {
            if (!video) {
              console.warn(`视频${videoId}不存在`);
              return false;
            }
            if (!video.url) {
              console.warn(`视频 ${video.id} (${video.title}) 缺少下载地址`);
              return false;
            }
            return true;
          });

        // 3. 初始化统计
        const results = {
          success: 0,
          fail: 0,
          errors: []
        };

        // 4. 动态延迟下载（初始300ms，每5个请求增加延迟）
        const BASE_DELAY = 1000;
        const CONCURRENCY_BATCH = 5;
        
        for (let i = 0; i < validVideos.length; i++) {
          const video = validVideos[i];
          
          try {
            // 5. 执行下载
            this.createDownloadLink(video.url, video.title);
            results.success++;
            
            // 6. 成功后才记录日志
            await downloadLog([video.id]);
          } catch (error) {
            // 7. 单任务错误捕获
            results.fail++;
            results.errors.push({
              id: video.id,
              title: video.title,
              reason: error.message || '未知错误'
            });
            console.error(`视频下载失败: ${video.title}`, error);
          }
          
          // 8. 动态延迟（每5个请求后增加延迟）
          const delay = BASE_DELAY + (Math.floor(i / CONCURRENCY_BATCH) * 100);
          if (i < validVideos.length - 1) {
            await new Promise(resolve => setTimeout(resolve, delay));
          }
        }

        // 9. 生成结果消息
        let resultMessage = `下载完成: ${results.success}个成功`;
        if (results.fail > 0) {
          resultMessage += `, ${results.fail}个失败`;
          // 附加失败详情（限制最多显示3条）
          const errorDetails = results.errors.slice(0, 3)
            .map(e => `${e.title}(${e.reason})`)
            .join('; ');
          resultMessage += `。失败视频: ${errorDetails}${results.errors.length > 3 ? '...' : ''}`;
        }
        
        // 10. 结果通知
        if (results.fail === validVideos.length) {
          this.$message.error('所有下载任务均失败');
        } else {
          results.fail > 0 
            ? this.$message.warning(resultMessage) 
            : this.$message.success(resultMessage);
        }

      } catch (globalError) {
        // 11. 全局错误捕获
        this.$message.error(`下载流程异常: ${globalError.message}`);
        console.error('全局下载异常:', globalError);
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

    async handleRateChange(videoId, newScore) {
      try {
        // 这里调用评分接口（需要根据实际API调整）
        await updateVideoScore({id: videoId, score: newScore })
        
        // 更新本地评分显示
        const videoIndex = this.composeVideo.findIndex(v => v.id === videoId)
        if (videoIndex > -1) {
          this.$set(this.composeVideo, videoIndex, {
            ...this.composeVideo[videoIndex],
            score: newScore
          })
        }
        
        this.$message.success('评分更新成功')
      } catch (error) {
        this.$message.error('评分更新失败')
      }
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
