<template>
  <page-content center>
    <lz-form
      :get-data="getData"
      :submit="onSubmit"
      :form.sync="form"
      :errors.sync="errors"
    >
      <lz-form-item label="姓名" required prop="name">
        <a-input v-model="form.name"/>
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
import {
  createAdminActor,
  editAdminActor,
  storeAdminActor,
  updateAdminActor,
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
        name: '',
        desc: '',
      },
      errors: {},
    }
  },
  methods: {
    async getData($form) {
      let data

      if ($form.realEditMode) {
        ({ data } = await editAdminActor($form.resourceId))
      } else {
        ({ data } = await createAdminActor())
      }

      return data
    },
    async onSubmit($form) {
      if ($form.realEditMode) {
        await updateAdminActor($form.resourceId, this.form)
      } else {
        await storeAdminActor(this.form)
      }
    },
  },
}
</script>
