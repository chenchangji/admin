<template>
  <page-content>
    <space class="my-1">
      <search-form :fields="search"/>
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
      <a-table-column title="创建人" data-index="name" :width="180"/>
      <a-table-column 
        title="添加时间" 
        data-index="created_at" 
        :width="180"
        :sorter="true"
        :sortOrder="activeSortField === 'created_at' ? activeSortOrder : undefined"
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
} from '@/api/admin-materials'
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
            { id: 21, name: 'B1-症状代入' },
            { id: 22, name: 'B2-疾病科普' },
            { id: 23, name: 'B3-病理' },
            { id: 26, name: 'B6-旧素材混剪' },
            { id: 31, name: 'C1-产品相关' },
            { id: 36, name: 'C6-旧素材混剪' },
            { id: 41, name: 'D1-价格优惠' },
            { id: 42, name: 'D2-厂家直发' },
            { id: 43, name: 'D3-厂家活动' },
            { id: 44, name: 'D6-旧素材混剪' }
          ]
        },
      ],
      adminMaterial: [],
      page: null,
      sortField: 'created_at',      // 新增：默认排序字段
      sortOrder: 'asc',           // 新增：默认排序方向
      activeSortField: 'created_at',  // 当前排序字段
      activeSortOrder: 'descend',     // 当前排序方向 ('ascend'|'descend')
      pagination: {                // 新增：分页控制
        current: 1,
        pageSize: 10
      }
    }
  },
  methods: {
    destroyAdminMaterial(id) {
      return async () => {
        await destroyAdminMaterial(id)
        this.adminMaterial = removeWhile(this.adminMaterial, (i) => i.id === id)
      }
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
        21: 'B1-症状代入',
        22: 'B2-疾病科普',
        23: 'B3-病理',
        26: 'B6-旧素材混剪',
        31: 'C1-产品相关',
        36: 'C6-旧素材混剪',
        41: 'D1-价格优惠',
        42: 'D2-厂家直发',
        43: 'D3-厂家活动',
        44: 'D6-旧素材混剪'
      };
      return subClassMap[subClassValue] || subClassValue; // 找不到则显示原值
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
        this.activeSortField = 'created_at';
        this.activeSortOrder = 'descend';
      }
      
      this.fetchData();
    },

    async fetchData() {
        const params = {
          ...this.$route.query,
          page: this.pagination.current,
          sort_field: this.activeSortField,
          sort_order: this.activeSortOrder === 'ascend' ? 'asc' : 'desc'
        };
        
        const { data } = await getAdminMaterials(params);
        this.adminMaterial = data.data;
        this.page = data.meta;
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
