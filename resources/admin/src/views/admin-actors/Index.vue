<template>
  <page-content>
    <space class="my-1">
      <search-form :fields="search"/>
    </space>

    <a-table
      row-key="id"
      :data-source="adminActor"
      bordered
      :scroll="{ x: 600 }"
      :pagination="false"
    >
      <a-table-column title="ID" data-index="id" :width="60"/>
      <a-table-column title="姓名" data-index="name"/>
      <a-table-column title="添加时间" data-index="created_at" :width="180"/>
      <a-table-column title="修改时间" data-index="updated_at" :width="180"/>
      <a-table-column title="操作" :width="100">
        <template #default="record">
          <space>
            <router-link :to="`/admin-actors/${record.id}/edit`">编辑</router-link>
            <lz-popconfirm :confirm="destroyAdminActor(record.id)">
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
  destroyAdminActor,
  getAdminActors,
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
          field: 'name',
          label: '字段名',
        },
      ],
      adminActor: [],
      page: null,
    }
  },
  methods: {
    destroyAdminActor(id) {
      return async () => {
        await destroyAdminActor(id)
        this.adminActor = removeWhile(this.adminActor, (i) => i.id === id)
      }
    },
  },
  watch: {
    $route: {
      async handler(newVal) {
        const { data: { data, meta } } = await getAdminActors(newVal.query)
        this.adminActor = data
        this.page = meta

        this.$scrollResolve()
      },
      immediate: true,
    },
  },
}
</script>
