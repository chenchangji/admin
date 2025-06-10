<template>
  <div class="login">
    <a-card class="login-card" >
      <div class="login-title">
          <h1>普生素材管理系统</h1>
      </div>
      <login-form
        ref="form"
        @keydown.enter.native="$refs.login.onAction"
      />
      <loading-action
        ref="login"
        type="primary"
        class="w-100"
        :action="onLogin"
        disable-on-success="2000"
      >
        <span>登录</span>
      </loading-action>
    </a-card>
  </div>
</template>

<script>
import LoginForm from '@c/LoginForm'
import LoadingAction from '@c/LoadingAction'
import Router from 'vue-router'

export default {
  name: 'Login',
  components: {
    LoadingAction,
    LoginForm,
  },
  computed: {
    appName() {
      return this.$store.getters.appName
    },
  },
  methods: {
    async onLogin() {
      await this.$refs.form.onSubmit()
      this.$router
        .push(this.$route.query.redirect || '/')
        .catch((e) => {
          if (!Router.isNavigationFailure(e)) {
            Promise.reject(e)
          }
        })
    },
  },
}
</script>

<style scoped lang="less">
.login {
  display: flex;
  justify-content: right;
  align-items: center;
  height: 100vh;
  background-color: #f0f2f5;
  background-image: url('login-bg.jpg');
  background-size: cover;
  background-position: center;
}

.login-card {
    width: 400px;
    border-radius: 8px;
    overflow: hidden;
    translate: -50%;
}

.login-title h1 {
  text-align: center;
  margin-bottom: 30px;
  color: #1890ff;
}
</style>