<template>
  <page-content center>
    <lz-form
      :get-data="getData"
      :submit="onSubmit"
      :form.sync="form"
      :errors.sync="errors"
    >
      <lz-form-item label="标题" required prop="title">
        <a-input v-model="form.title"/>
      </lz-form-item>

      <lz-form-item label="视频来源"  prop="type">
        <a-select v-model="form.type" placeholder="请选择视频来源">
          <a-select-option :value="1">信息流视频</a-select-option>
          <a-select-option :value="2">种草视频</a-select-option>
        </a-select>
      </lz-form-item>
     
      <lz-form-item label="视频分类" required prop="class">
        <a-select
          v-model="form.class"
          placeholder="请选择分类"
          @change="handleClassChange"
          style="width: 200px; margin-right: 10px"
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
          v-model="form.sub_class"
          placeholder="请选择子分类"
          :disabled="!form.class"
          style="width: 200px"
        >
          <a-select-option
            v-for="item in subclassOptions"
            :key="item.value"
            :value="item.value"
          >
            {{ item.label }}
          </a-select-option>
        </a-select>
      </lz-form-item>

      <lz-form-item label="投放类型"  prop="tag">
        <a-select v-model="form.tag" placeholder="请选择投放类型">
          <a-select-option :value="1">直购</a-select-option>
          <a-select-option :value="2">加粉</a-select-option>
        </a-select>
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
      <lz-form-item label="演员"  prop="actor_ids">
        <a-select v-model="form.actor_ids"   mode="multiple"  placeholder="请选择演员">
          <a-select-option 
            v-for="item in actorOptions" 
            :key="item.value" 
            :value="item.value"
          >
            {{ item.label }}
          </a-select-option>
        </a-select>
      </lz-form-item>
      <lz-form-item label="视频上传" required prop="url">
        <a-upload
          name="video"
          list-type="text"
          :file-list="fileList"
          :before-upload="beforeUpload"
          :customRequest="customUpload"
          @change="handleUploadChange"
          accept="video/*"
        >
          <a-button> <upload-outlined /> 选择视频文件 </a-button>
          <template #tip>
            <div class="ant-upload-text">
              支持 MP4、MOV 等视频格式，大小不超过 100MB
              <a-progress 
                v-if="uploadProgress > 0 && uploadProgress < 100" 
                :percent="uploadProgress" 
                size="small" 
              />
            </div>
          </template>
        </a-upload>
      </lz-form-item>
      <lz-form-item label="备注"  prop="desc">
        <a-input v-model="form.desc"/>
      </lz-form-item>
    </lz-form>
  </page-content>
</template>

<script>
import LzForm from '@c/LzForm'
import LzFormItem from '@c/LzForm/LzFormItem'
import PageContent from '@c/PageContent'
import { message } from 'ant-design-vue'
import OSS from 'ali-oss'
import axios from 'axios' // Add axios import
import {
  createAdminMaterial,
  editAdminMaterial,
  storeAdminMaterial,
  updateAdminMaterial,
} from '@/api/admin-materials'
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
        class: '',
        sub_class: '',
        actor_ids: [],
        screen_type: '',
        desc: '',
        url: '',
        type: '',
        product_id: '',
        product_format: '',
        tag: '',
      },
      fileList: [],
      ossClient: null,
      uploadProgress: 0,
      errors: {},
      actorOptions: [], // 演员下拉选项
      // 一级分类选项
      classOptions: [
        { value: 1, label: '营销内容' },
        { value: 2, label: '痛点/症状' },
        { value: 3, label: '产品背书' },
        { value: 4, label: '引导购买' }
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
      // 当前可选的二级子分类（初始为空）
      subclassOptions: [],
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
        ({ data } = await editAdminMaterial($form.resourceId))
         this.handleClassChange(data.class);
         this.handleProductChange(data.product_id);
      } else {
        ({ data } = await createAdminMaterial())
      }

      return data
    },
    async onSubmit($form) {
      if ($form.realEditMode) {
        await updateAdminMaterial($form.resourceId, this.form)
      } else {
        await storeAdminMaterial(this.form)
      }
    },
    async initOSSClient() {
      try {
        const { data } = await axios.get('/admin-api/oss/auth')
        return new OSS({
          region: data.region,
          accessKeyId: data.AccessKeyId,
          accessKeySecret: data.AccessKeySecret,
          stsToken: data.SecurityToken,
          bucket: data.bucket,
          refreshSTSToken: async () => {
            const res = await axios.get('/admin-api/oss/auth')
            return {
              accessKeyId: res.data.accessKeyId,
              accessKeySecret: res.data.accessKeySecret,
              stsToken: res.data.securityToken,
            }
          },
        })
      } catch (error) {
        message.error('OSS 初始化失败！')
        console.error(error)
        return null
      }
    },
    async beforeUpload(file) {
      const isVideo = file.type.includes('video/')
      const isLt200M = file.size / 1024 / 1024 < 200
      
      if (!isVideo) {
        message.error('只能上传视频文件！')
        return false
      }
      if (!isLt200M) {
        message.error('视频大小不能超过 200MB！')
        return false
      }
      
      if (!this.ossClient) {
        this.ossClient = await this.initOSSClient()
        if (!this.ossClient) return false
      }
      
      return true
    },
    async customUpload(options) {
      const { file, onProgress, onSuccess, onError } = options
      
      try {
        const fileName = `videos/${Date.now()}_${file.name}`
        
        const result = await this.ossClient.multipartUpload(fileName, file, {
          progress: (p) => {
            this.uploadProgress = Math.round(p * 100)
            onProgress({ percent: this.uploadProgress })
          },
        })
        
        this.form.url = result.res.requestUrls[0].split('?')[0]
        onSuccess(result, file)
        message.success('视频上传成功！')
      } catch (error) {
        console.error('上传失败:', error)
        onError(error)
        message.error('视频上传失败！')
      }
    },
    handleUploadChange(info) {
      this.fileList = [info.file]
    },

    async fetchActorOptions() {
      try {
        const response = await getAdminActorList(); // 调用API获取数据
        // 假设接口返回的数据格式为 [{id: 1, name: '张三'}, ...]
        this.actorOptions = response.data.map(item => ({
          value: item.id,
          label: item.name,
        }));
        
      } catch (error) {
        console.error('获取演员列表失败:', error);
        this.$message.error('获取演员列表失败');
      }
    },
    handleClassChange(value) {
      this.form.sub_class = undefined; // 清空二级选择
      this.subclassOptions = this.subclassMap[value] || []; // 更新二级选项
    },
    handleProductChange(value) {
      this.form.product_format = undefined; // 清空规格选择
      this.productFormatOptions = this.productFormatMap[value] || []; // 更新规格选项
    },

  },
}
</script>
