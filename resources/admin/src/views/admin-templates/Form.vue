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
      <lz-form-item label="产品来源"  prop="product_type">
        <a-select v-model="form.product_type" placeholder="请选择产品来源">
          <a-select-option :value="1">信息流视频</a-select-option>
          <a-select-option :value="2">种草视频</a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="横竖屏" required prop="screen_type">
        <a-select v-model="form.screen_type" placeholder="请选择横竖屏">
          <a-select-option :value="1">横屏</a-select-option>
          <a-select-option :value="2">竖屏</a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="水印图"  prop="water_image_id">
        <a-select v-model="form.water_image_id"    placeholder="请选择水印图">
          <a-select-option 
            v-for="item in waterImageOptions" 
            :key="item.value" 
            :value="item.value"
          >
            {{ item.label }}
          </a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="投放类型"  prop="product_tag">
        <a-select v-model="form.product_tag" placeholder="请选择投放类型">
          <a-select-option :value="1">直购</a-select-option>
          <a-select-option :value="2">加粉</a-select-option>
        </a-select>
      </lz-form-item>
      
      <lz-form-item label="拼接规则" required prop="class_rules">
        <div v-for="(rule, index) in class_rules" :key="index" class="rule-item">
          <a-select
            v-model="rule.classValue"
            @change="(value) => handleClassChange(index, value)"
            placeholder="请选择大类"
            style="width: 180px; margin-right: 8px;"
          >
            <a-select-option 
              v-for="item in classOptions"
              :key="item.value"
              :value="item.value"
            >
              {{ item.label }}
            </a-select-option>
          </a-select>
  
          <a-select
            v-model="rule.subClassValue"
            @change="(value) => handleSubClassChange(index, value)"
            placeholder="请选择子类"
            :disabled="!rule.classValue"
            style="width: 180px; margin-right: 8px;"
          >
            <a-select-option
              v-for="sub in getFilteredSubClasses(rule.classValue)"
              :key="sub.value"
              :value="sub.value"
            >
              {{ sub.label }}
            </a-select-option>
          </a-select>
  
          <a-select
            v-model="rule.actorValue"
            placeholder="请选择演员"
            style="width: 220px; margin-right: 8px;"
            mode="multiple"
          >
            <a-select-option
              v-for="actor in actorOptions"
              :key="actor.value"
              :value="actor.value"
            >
              {{ actor.label }}
            </a-select-option>
          </a-select>
          
          <a-button
            v-if="class_rules.length > 1"
            @click="removeRule(index)"
            type="danger"
            icon="delete"
            style="margin-left: 8px"
          />
        </div>
        
        <a-button
          @click="addRule"
          type="dashed"
          icon="plus"
          style="margin-top: 10px"
        >
          添加
        </a-button>
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

import {
  getAdminWaterImageList,
} from '@/api/admin-water-images'

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
        water_image_id: '',
        product_format: '',
        product_type: '',
        product_tag: '',
        screen_type: '',
        is_water_mark: '',
        class_rules: '',
        range: '',
        exclude_actor_ids: [],
        exclude_sub_class: [],
      },
      errors: {},
      actorOptions: [], // 演员下拉选项
      waterImageOptions: [], // 演员下拉选项
      subClassOptions: [ // 营销内容的子分类
          { value: 11, label: 'A1-营销内容' },
          { value: 12, label: 'A2-价格营销' },
          { value: 14, label: 'A4-营销内容-合规' },
          { value: 15, label: 'A5-价格营销-合规' },
          { value: 16, label: 'A6-旧素材混剪' },
          { value: 17, label: 'A7-' },
          { value: 18, label: 'A8-' },
          { value: 21, label: 'B1-症状代入' },
          { value: 22, label: 'B2-疾病科普' },
          { value: 23, label: 'B3-病理' },
          { value: 26, label: 'B6-旧素材混剪' },
          { value: 27, label: 'B7-' },
          { value: 28, label: 'B8-' },
          { value: 31, label: 'C1-产品相关' },
          { value: 36, label: 'C6-旧素材混剪' },
          { value: 37, label: 'C7-' },
          { value: 38, label: 'C8-' },
          { value: 41, label: 'D1-价格优惠' },
          { value: 42, label: 'D2-厂家直发' },
          { value: 43, label: 'D3-厂家活动' },
          { value: 44, label: 'D6-旧素材混剪' },
          { value: 47, label: 'D7-' },
          { value: 48, label: 'D8-' }
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
      productFormatOptions: [],
      classOptions: [
        { label: 'A类-营销内容', value: '1' },
        { label: 'B类-痛点/症状', value: '2' },
        { label: 'C类-产品背书', value: '3' },
        { label: 'D类-引导购买', value: '4' }
      ],
      // 二级子分类映射（根据一级分类动态变化）
      subclassMap: {
        1: [ // 营销内容的子分类
          { value: 11, label: 'A1-营销内容' },
          { value: 12, label: 'A2-价格营销' },
          { value: 14, label: 'A4-营销内容-合规' },
          { value: 15, label: 'A5-价格营销-合规' },
          { value: 16, label: 'A6-旧素材混剪' },
          { value: 17, label: 'A7-' },
          { value: 18, label: 'A8-' }
        ],
        2: [ // 痛点/症状的子分类
          { value: 21, label: 'B1-症状代入' },
          { value: 22, label: 'B2-疾病科普' },
          { value: 23, label: 'B3-病理' },
          { value: 26, label: 'B6-旧素材混剪' },
          { value: 27, label: 'B7-' },
          { value: 28, label: 'B8-' }
        ],
        3: [ // 产品背书的子分类
          { value: 31, label: 'C1-产品相关' },
          { value: 36, label: 'C6-旧素材混剪' },
          { value: 37, label: 'C7-' },
          { value: 38, label: 'C8-' }
        ],
        4: [ // 引导购买的子分类
          { value: 41, label: 'D1-价格优惠' },
          { value: 42, label: 'D2-厂家直发' },
          { value: 43, label: 'D3-厂家活动' },
          { value: 44, label: 'D6-旧素材混剪' },
          { value: 47, label: 'D7-' },
          { value: 48, label: 'D8-' }
        ]
      },
      class_rules: [
        { classValue: null, subClassValue: null, actorValue: [] }
      ],

    }
  },

  created() {
    this.fetchActorOptions(); // 组件创建时获取数据
    this.fetchWaterImageOptions();
  },

  methods: {
    async getData($form) {
      let data

      if ($form.realEditMode) {
        ({ data } = await editAdminTemplate($form.resourceId))
        this.handleProductChange(data.product_id);
        // 回填规则数据
        if (data.class_rules) {
          try {
            const rules = JSON.parse(data.class_rules);
            this.class_rules = rules.map(rule => ({
              classValue: rule.class,
              subClassValue: rule.sub_class,
              actorValue: rule.actor_ids || []
            }));
          } catch (e) {
            console.error('规则解析失败', e);
            this.class_rules = [{ classValue: null, subClassValue: null, actorValue: [] }];
          }
        }
      } else {
        ({ data } = await createAdminTemplate());
        this.class_rules = [{ classValue: null, subClassValue: null, actorValue: [] }];
      }

      return data
    },
    async onSubmit($form) {
      this.prepareFormData(); // 转换规则数据
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

    async fetchWaterImageOptions() {
      try {
        const response = await getAdminWaterImageList(); // 调用API获取数据
        // 假设接口返回的数据格式为 [{id: 1, name: '营销内容'}, ...]
        this.waterImageOptions = response.data.map(item => ({
          value: item.id,
          label: item.title,
        }));
        
      } catch (error) {
        console.error('获取水印图列表失败:', error);
        this.$message.error('获取水印图列表失败');
      }
    },
    handleProductChange(value) {
      this.form.product_format = undefined; // 清空规格选择
      this.productFormatOptions = this.productFormatMap[value] || []; // 更新规格选项
    },

    // 获取过滤后的子类
    getFilteredSubClasses(classValue) {
      if (!classValue) return [];
      return this.subclassMap[classValue] || [];
    },
    
    // 添加新规则
    addRule() {
      if (this.class_rules.length >= 8 ) {
        this.$message.warning('至多添加八条规则');
        return;
      }
      this.class_rules.push({
        classValue: null,
        subClassValue: null,
        actorValue: []
      });
    },
    
    // 移除规则
    removeRule(index) {
      if (this.class_rules.length <= 4) {
        this.$message.warning('至少保留四条规则');
        return;
      }
      this.class_rules.splice(index, 1);
    },
    
    // 大类变更处理
    handleClassChange(index, value) {
      const rule = this.class_rules[index];
      rule.subClassValue = null;
      rule.actorValue = [];
    },
    
    // 子类变更处理
    handleSubClassChange(index, value) {
      const rule = this.class_rules[index];
      rule.actorValue = [];
    },
    
    // 在提交前转换规则格式
    prepareFormData() {
      // 转换规则为后端需要的格式
      this.form.class_rules = JSON.stringify(
        this.class_rules.map(rule => ({
          class: rule.classValue,
          sub_class: rule.subClassValue,
          actor_ids: rule.actorValue
        }))
      );
    },
    // 编辑时回填数据
    async getData($form) {
      let data

      if ($form.realEditMode) {
        ({ data } = await editAdminTemplate($form.resourceId));
        this.handleProductChange(data.product_id);
        
        // 回填规则数据
        if (data.class_rules) {
          try {
            const rules = JSON.parse(data.class_rules);
            this.class_rules = rules.map(rule => ({
              classValue: rule.class,
              subClassValue: rule.sub_class,
              actorValue: rule.actor_ids || []
            }));
          } catch (e) {
            console.error('规则解析失败', e);
            this.class_rules = [{ classValue: null, subClassValue: null, actorValue: [] }];
          }
        }
      } else {
        ({ data } = await createAdminTemplate());
        this.class_rules = [{ classValue: null, subClassValue: null, actorValue: [] }];
      }

      return data;
    }
  },
}
</script>

<style scoped>
.rule-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}
</style>
