<template>
  <page-content>
    <space class="my-1">
      <search-form :fields="search"/>
    </space>

    <a-table
      row-key="id"
      :data-source="composeVideo"
      bordered
      :scroll="{ x: 600 }"
      :pagination="false"
    >
     <a-table-column title="ID" data-index="id" :width="60"/>
      <a-table-column title="标题" data-index="title"/>
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
      <a-table-column title="添加时间" data-index="created_at" :width="180"/>
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
      ],
      composeVideo: [],
      page: null,
    }
  },
  methods: {
    destroyComposeVideo(id) {
      return async () => {
        await destroyComposeVideo(id)
        this.composeVideo = removeWhile(this.composeVideo, (i) => i.id === id)
      }
    },
    getProductLabel(classValue) {
      const classMap = {
        1: '舒筋健腰丸',
        2: '清血八味片'
      };
      return classMap[classValue] || classValue; // 找不到则显示原值
    },
    getProductFormatLabel(classValue) {
      const classMap = {
        1: '24片',
        2: '120片'
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
    handlePlayVideo(url) {
      // 使用弹窗播放视频
      this.$info({
        title: '视频播放',
        width: '60%',
        content: (
          <video controls autoplay style="width: 100%">
            <source src={url} type="video/mp4" />
          </video>
        ),
      });
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
