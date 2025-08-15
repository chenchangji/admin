<template>
  <page-content>
    <space class="my-1">
      <search-form :fields="search" />
    </space>

    <a-table
      row-key="id"
      :data-source="waterImage"
      bordered
      :scroll="{ x: 600 }"
      :pagination="false"
    >
      <a-table-column title="ID" data-index="id" />
      <a-table-column title="标题" data-index="title" />
      <a-table-column title="类型" data-index="type" key="type">
        <template slot-scope="text">
            {{ getTypeLabel(text) }}
        </template>
      </a-table-column>
      <a-table-column title="水印图" key="url" :width="120">
        <template #default="{ url }">
          <div v-if="url" style="cursor: pointer">
            <img :width="60" :src="url" alt="水印图" />
          </div>
          <span v-else>无图片</span>
        </template>
      </a-table-column>
      <a-table-column title="添加时间" data-index="created_at" :width="180" />
      <a-table-column title="操作" :width="100">
        <template #default="{ id }">
          <space>
            <router-link :to="`/admin-water-images/${id}/edit`">编辑</router-link>
            <lz-popconfirm @confirm="() => handleDelete(id)">
              <a class="error-color" href="javascript:void(0);">删除</a>
            </lz-popconfirm>
          </space>
        </template>
      </a-table-column>
    </a-table>
    <lz-pagination :page="page" />
  </page-content>
</template>

<script>
import LzPagination from '@c/LzPagination'
import LzPopconfirm from '@c/LzPopconfirm'
import PageContent from '@c/PageContent'
import SearchForm from '@c/SearchForm'
import Space from '@c/Space'
import {
  destroyWaterImage,
  getWaterImages,
} from '@/api/admin-water-images'
import { removeWhile } from '@/libs/utils'

export default {
  name: 'WaterImageIndex',
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
        { field: 'id', label: 'ID' },
        { field: 'title', label: '标题' },
      ],
      waterImage: [],
      page: null,
    }
  },
  created() {
    this.fetchData()
  },
  watch: {
    '$route.query': {
      handler() {
        this.fetchData()
      },
      deep: true
    }
  },
  methods: {
    async fetchData() {
      try {
        const { data: { data, meta } } = await getWaterImages(this.$route.query)
        this.waterImage = data
        this.page = meta
      } catch (error) {
        console.error('获取水印图数据失败:', error)
        this.$message.error('数据加载失败，请重试')
      } finally {
        this.$scrollResolve?.()
      }
    },
    getTypeLabel(typeValue) {
      const typeMap = {
        1: '图片',
        2: 'GIF动图',
      };
      return typeMap[typeValue] || typeValue; // 找不到则显示原值
    },
    async handleDelete(id) {
      try {
        await destroyWaterImage(id)
        this.waterImage = removeWhile(this.waterImage, item => item.id === id)
        this.$message.success('删除成功')
      } catch (error) {
        console.error('删除水印图失败:', error)
        this.$message.error('删除失败，请重试')
      }
    }
  }
}
</script>