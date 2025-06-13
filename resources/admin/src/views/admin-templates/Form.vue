<template>
  <page-content center>
    <lz-form
      :get-data="getData"
      :submit="onSubmit"
      :form.sync="form"
      :errors.sync="errors"
    >
      <lz-form-item label="模板名称" required prop="title">
        <a-input v-model="form.title"/>
      </lz-form-item>
      <lz-form-item label="关联产品" required prop="product_id">
        <a-select
          v-model="form.product_id"
          placeholder="请选择产品"
          @change="handleProductChange"
          style="width: 200px; margin-right: 10px"
        >
          <a-select-option
            v-for="item in productOptions"
            :key="item.value"
            :value="item.value"
          >
            {{ item.label }}
          </a-select-option>
        </a-select>

        <a-select
          v-model="form.product_format"
          placeholder="请选择产品规格"
          :disabled="!form.product_id"
          style="width: 200px"
        >
          <a-select-option
            v-for="item in productFormatOptions"
            :key="item.value"
            :value="item.value"
          >
            {{ item.label }}
          </a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="横竖屏" required prop="screen_type">
        <a-select v-model="form.screen_type" placeholder="请选择横竖屏">
          <a-select-option :value="1">横屏</a-select-option>
          <a-select-option :value="2">竖屏</a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="投放类型"  prop="product_tag">
        <a-select v-model="form.product_tag" placeholder="请选择投放类型">
          <a-select-option :value="1">直购</a-select-option>
          <a-select-option :value="2">加粉</a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="拼接规则" required  prop="class_rules">
        <a-input placeholder="格式如：A1+A2+C1+D1+C2+D2" v-model="form.class_rules"/>
      </lz-form-item>
      <lz-form-item label="素材优先选用时间"  prop="range">
        <a-select v-model="form.range" placeholder="请选择优先选用时间">
          <a-select-option :value="1">7天内</a-select-option>
          <a-select-option :value="2">30天内</a-select-option>
          <a-select-option :value="3">90天内</a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="过滤演员"  prop="exclude_actor_ids">
        <a-select v-model="form.exclude_actor_ids"   mode="multiple"  placeholder="请选择要过滤的演员">
          <a-select-option 
            v-for="item in actorOptions" 
            :key="item.value" 
            :value="item.value"
          >
            {{ item.label }}
          </a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="过滤子分类"  prop="exclude_sub_class">
        <a-select v-model="form.exclude_sub_class"   mode="multiple"  placeholder="请选择要过滤的子分类">
          <a-select-option 
            v-for="item in subClassOptions" 
            :key="item.value" 
            :value="item.value"
          >
            {{ item.label }}
          </a-select-option>
        </a-select>
      </lz-form-item>
    </lz-form>
  </page-content>
</template>

<script>
import LzForm from '@c/LzForm'
import LzFormItem from '@c/LzForm/LzFormItem'
import PageContent from '@c/PageContent'
import {
  createAdminTemplate,
  editAdminTemplate,
  storeAdminTemplate,
  updateAdminTemplate,
} from '@/api/admin-templates'
import {
  getAdminActorList,
} from '@/api/admin-actors'

export default {
  name: 'Form',
  components: {
    LzForm,
    LzFormItem,
    PageContent,
  },  
  data() {
    return {
      form: {
        title: '',
        product_id: '',
        product_format: '',
        product_tag: '',
        screen_type: '',
        class_rules: '',
        range: '',
        exclude_actor_ids: [],
        exclude_sub_class: [],
      },
      errors: {},
      actorOptions: [], // 演员下拉选项
      subClassOptions: [ // 营销内容的子分类
          { value: 11, label: 'A1-营销内容' },
          { value: 12, label: 'A2-价格营销' },
          { value: 14, label: 'A4-营销内容-合规' },
          { value: 15, label: 'A5-价格营销-合规' },
          { value: 16, label: 'A6-旧素材混剪' },
          { value: 21, label: 'B1-症状代入' },
          { value: 22, label: 'B2-疾病科普' },
          { value: 23, label: 'B3-病理' },
          { value: 26, label: 'B6-旧素材混剪' },
          { value: 31, label: 'C1-产品相关' },
          { value: 36, label: 'C6-旧素材混剪' },
          { value: 41, label: 'D1-价格优惠' },
          { value: 42, label: 'D2-厂家直发' },
          { value: 43, label: 'D3-厂家活动' },
          { value: 44, label: 'D6-旧素材混剪' }
        ],
      // 产品选项
      productOptions: [
        { value: 1, label: '舒筋健腰丸' },
        { value: 2, label: '清血八味片' },
        { value: 3, label: '咽康' }
      ],
      // 产品规格映射（根据产品选项动态变化）
      productFormatMap: {
        1: [ // 舒筋
          { value: 1, label: '拆零' },
          { value: 2, label: '大盒' },
        ],
        2: [ // 清血
          { value: 3, label: '24片' },
          { value: 4, label: '120片' }
        ],
        3: [ // 咽康
          { value: 5, label: '18片' },
          { value: 6, label: '40片' }
        ]
      },
      // 当前可选的二级子分类（初始为空）
      productFormatOptions: []
    }
  },

  created() {
    this.fetchActorOptions(); // 组件创建时获取数据
  },

  methods: {
    async getData($form) {
      let data

      if ($form.realEditMode) {
        ({ data } = await editAdminTemplate($form.resourceId))
        this.handleProductChange(data.product_id);
      } else {
        ({ data } = await createAdminTemplate())
      }

      return data
    },
    async onSubmit($form) {
      if ($form.realEditMode) {
        await updateAdminTemplate($form.resourceId, this.form)
      } else {
        await storeAdminTemplate(this.form)
      }
    },

    async fetchActorOptions() {
      try {
        const response = await getAdminActorList(); // 调用API获取数据
        // 假设接口返回的数据格式为 [{id: 1, name: '营销内容'}, ...]
        this.actorOptions = response.data.map(item => ({
          value: item.id,
          label: item.name,
        }));
        
      } catch (error) {
        console.error('获取演员列表失败:', error);
        this.$message.error('获取演员列表失败');
      }
    },
    handleProductChange(value) {
      this.form.product_format = undefined; // 清空规格选择
      this.productFormatOptions = this.productFormatMap[value] || []; // 更新规格选项
    },
  },
}
</script>
