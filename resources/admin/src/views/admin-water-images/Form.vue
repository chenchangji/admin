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

      <lz-form-item label="图片类型"  prop="type">
        <a-select v-model="form.type" placeholder="请选择图片类型">
          <a-select-option :value="1">图片</a-select-option>
          <a-select-option :value="2">GIF动图</a-select-option>
        </a-select>
      </lz-form-item>
      
      <!-- 图片上传 -->
      <lz-form-item label="图片上传" required prop="url">
        <a-upload
          name="image"
          list-type="picture-card"
          :file-list="imageFileList"
          :before-upload="beforeImageUpload"
          :custom-request="customImageUpload"
          @change="handleImageChange"
          @remove="handleImageRemove"
          accept="image/*"
          :show-upload-list="true"
        >
          <div v-if="!isImageUploading">
            <plus-outlined />
            <div class="ant-upload-text">上传图片</div>
          </div>
          <div v-else>
            <a-progress 
              type="circle" 
              :percent="imageUploadProgress" 
              :width="32" 
              status="active"
            />
          </div>
          <template #tip>
            <div class="ant-upload-text">
              支持 JPG、PNG 等格式，大小不超过 2MB
              <span v-if="imageUploadError" class="error-text">{{ imageUploadError }}</span>
            </div>
          </template>
        </a-upload>
      </lz-form-item>
      
    </lz-form>
  </page-content>
</template>

<script>
import LzForm from '@c/LzForm'
import LzFormItem from '@c/LzForm/LzFormItem'
import PageContent from '@c/PageContent'
import { PlusOutlined } from '@ant-design/icons-vue'
import { message } from 'ant-design-vue'
import OSS from 'ali-oss'
import axios from 'axios'
import { v4 as uuidv4 } from 'uuid'
import {
  createWaterImage,
  editWaterImage,
  storeWaterImage,
  updateWaterImage,
} from '@/api/admin-water-images'

export default {
  name: 'Form',
  components: {
    LzForm,
    LzFormItem,
    PageContent,
    PlusOutlined
  },
  data() {
    return {
      form: {
        title: '',
        type:'',
        url: ''
      },
      errors: {},
      imageFileList: [],
      imageUploadProgress: 0,
      isImageUploading: false,
      imageUploadError: null,
      ossClient: null,
      // 标记是否已初始化OSS客户端
      ossInitialized: false
    }
  },
  async created() {
    // 异步初始化OSS客户端
    await this.initOSSClient();
  },
  methods: {
    // 初始化OSS客户端
    async initOSSClient() {
      try {
        if (this.ossInitialized) return;
        
        const { data } = await axios.get('/admin-api/oss/auth')
        this.ossClient = new OSS({
          region: data.region,
          accessKeyId: data.AccessKeyId,
          accessKeySecret: data.AccessKeySecret,
          stsToken: data.SecurityToken,
          bucket: data.bucket,
          refreshSTSToken: async () => {
            try {
              const res = await axios.get('/admin-api/oss/auth')
              return {
                accessKeyId: res.data.AccessKeyId,
                accessKeySecret: res.data.AccessKeySecret,
                stsToken: res.data.SecurityToken,
              }
            } catch (refreshError) {
              console.error('刷新OSS凭证失败:', refreshError)
              throw refreshError
            }
          },
        })
        this.ossInitialized = true
      } catch (error) {
        console.error('OSS初始化失败:', error)
        message.error('OSS初始化失败，图片上传功能不可用')
      }
    },
        
    // 图片上传前校验
    beforeImageUpload(file) {
      const isImage = file.type.startsWith('image/')
      const isLt2M = file.size / 1024 / 1024 < 10
      
      if (!isImage) {
        this.imageUploadError = '请上传图片文件!'
        message.error('只能上传图片文件')
        return false
      } else if (!isLt2M) {
        this.imageUploadError = '图片大小不能超过2MB!'
        message.error('图片大小不能超过2MB')
        return false
      }

      if (!this.form.title) {
        this.imageUploadError = '请输入水印标题!'
        message.error('请输入水印标题')
        return false
      } 

      
      this.imageUploadError = null
      return true
    },
    
    // 图片自定义上传到OSS
    async customImageUpload({ file, onProgress, onSuccess, onError }) {
      // 确保OSS客户端已初始化
      if (!this.ossClient && !(await this.initOSSClient())) {
        this.imageUploadError = '图片上传服务初始化失败'
        onError(new Error('OSS client not initialized'))
        return
      }

      this.isImageUploading = true
      this.imageUploadProgress = 0
      
      try {
        // 生成唯一文件名
        const fileName = this.form.title + (this.form.type == 1 ? '.png' : '.gif')
        // 上传到OSS
        const result = await this.ossClient.put(`images/${fileName}`, file, {
          progress: (p) => {
            const percent = Math.round(p * 100)
            this.imageUploadProgress = percent
            onProgress({ percent })
          }
        })
        
        // 保存图片URL到表单数据
        this.form.url = result.url
        onSuccess(result, file)
      } catch (error) {
        console.error('上传到OSS失败:', error)
        this.imageUploadError = '上传失败: ' + (error.message || '请重试')
        message.error('图片上传失败: ' + (error.message || '服务器错误'))
        onError(error)
      } finally {
        this.isImageUploading = false
      }
    },
    
    // 处理图片上传状态变化
    handleImageChange({ file, fileList }) {
      this.imageFileList = fileList
      
      // 处理上传错误
      if (file.status === 'error') {
        this.imageUploadError = file.error?.message || '图片上传失败'
      }
      
      // 上传完成时处理
      if (file.status === 'done') {
        this.imageUploadError = null
      }
    },
    
    // 处理图片删除
    handleImageRemove() {
      this.form.url = ''
      this.imageUploadError = null
    },
    
    async getData($form) {
      try {
        let data

        if ($form.realEditMode) {
          ({ data } = await editWaterImage($form.resourceId))
        } else {
          ({ data } = await createWaterImage())
        }
        
        // 回填已有图片（编辑模式）
        if (data.url) {
          this.imageFileList = [{
            uid: '-1',
            name: '已上传图片',
            status: 'done',
            url: data.url
          }]
          this.form.url = data.url
        }

        return data
      } catch (error) {
        console.error('获取表单数据失败:', error)
        message.error('加载数据失败')
      }
    },
    
    async onSubmit($form) {
      // 提交前检查图片是否已上传
      if (!this.form.url) {
        this.errors.url = '请上传图片'
        message.error('请上传图片')
        return
      }
      
      try {
        if ($form.realEditMode) {
          await updateWaterImage($form.resourceId, this.form)
          message.success('更新成功')
        } else {
          await storeWaterImage(this.form)
          message.success('创建成功')
        }
        
        // 重置上传状态
        this.imageUploadProgress = 0
        this.imageUploadError = null
        
        // 提交成功后返回列表页
        this.$router.push({ name: 'water-images-list' })
      } catch (error) {
        console.error('表单提交失败:', error)
        message.error('提交失败: ' + (error.response?.data?.message || error.message || '服务器错误'))
      }
    },
  },
}
</script>

<style scoped>
.error-text {
  color: #ff4d4f;
  margin-left: 10px;
}
.ant-upload-text {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.45);
  margin-top: 8px;
}
</style>