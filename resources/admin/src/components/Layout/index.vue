<template>
  <a-layout class="h-100">
    <a-layout-sider
      :width="fullWidth"
      breakpoint="lg"
      :collapsed-width="collapsedWith"
      :collapsible="true"
      :collapsed="collapsed"
      :trigger="null"
      :class="{ sider: true, 'sider-mini-width': miniWidth }"
      v-click-outside="onClickOutside"
    >
      <router-link to="/" class="vertical-layout logo" :title="appName">
        <span v-if="appLogo" class="logo-wrapper">
          <img :src="appLogo" class="logo-img">
        </span>
        <span v-show="!appLogo || !collapsed" class="app-name">{{ appName }}</span>
      </router-link>
      <div class="ma-1">
        <a-input v-model="q" placeholder="搜索菜单"/>
      </div>
      <side-menu :q="q"/>
    </a-layout-sider>
    <a-layout
      :class="{ 'layout-main': true }"
      :style="{ paddingLeft: `${siderWidth}px`}"
    >
      <a-layout-header class="pa-0">
        <navbar/>
      </a-layout-header>
      <a-layout-content class="pa-2">
        <breadcrumb class="pb-2"/>
        <div class="pa-2" style="background: #fff">
          <transition name="fade-transform" mode="out-in">
            <template v-if="$route.query._refresh"/>
            <lz-keep-alive v-else :include="$store.state.include">
              <router-view :key="$route.name"/>
            </lz-keep-alive>
          </transition>
        </div>
      </a-layout-content>
      <a-layout-footer style="text-align: left;">LZ - admin</a-layout-footer>
    </a-layout>
  </a-layout>
</template>

<script>
import SideMenu from './components/SideMenu'
import { mapGetters, mapState } from 'vuex'
import Navbar from './components/Navbar'
import { getUrl } from '@/libs/utils'
import { SYSTEM_BASIC } from '@/libs/constants'
import Breadcrumb from './components/Breadcrumb'
import LzKeepAlive from '@c/LzKeepAlive'

export default {
  name: 'Layout',
  components: {
    Navbar,
    SideMenu,
    Breadcrumb,
    LzKeepAlive,
  },
  data: () => ({
    q: '',
  }),
  computed: {
    ...mapState({
      miniWidth: (state) => state.miniWidth,
      collapsed: (state) => !state.sideMenu.opened,
    }),
    ...mapGetters([
      'appName',
      'getConfig',
    ]),
    appLogo() {
      return getUrl(this.getConfig(SYSTEM_BASIC.SLUG + '.' + SYSTEM_BASIC.APP_LOGO_SLUG))
    },
    collapsedWith() {
      return this.miniWidth ? 0 : 80
    },
    fullWidth() {
      return 220
    },
    siderWidth() {
      return this.miniWidth ? 0 : (this.collapsed ? this.collapsedWith : this.fullWidth)
    },
  },
  methods: {
    onClickOutside() {
      if (!this.miniWidth || this.collapsed) {
        return
      }
      this.$store.commit('SET_OPENED', false)
    },
  },
}
</script>

<style scoped lang="less">
@import "~@/styles/vars";

.sider {
  height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
  position: fixed;
  z-index: 2;
}

.sider-mini-width {
  z-index: 2;
}

.layout-main {
  transition: all 0.2s;
  width: 100%;
  overflow-x: visible !important;
}

.ant-layout-content {
  min-height: auto;
  background: @layout-body-background;
}

.logo {
  height: 64px;
  padding: 0 14px;
  justify-content: left;
}

.app-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #fff;
  font-size: 18px;
  line-height: 36px;
  display: inline-block;
  width: 100%;
}

.logo-img {
  max-width: 100%;
  height: 90px;
  border-radius: 4px;
}

.logo-wrapper {
  min-width: 52px;
  width: 52px;
  height: 64px;
  min-height: 64px;
}

.content-main {
  padding-top: 64px;
  display: flex;
  flex-direction: column;
  min-height: 100%;
}

/* fade-transform */
.fade-transform-leave-active,
.fade-transform-enter-active {
  transition: all .3s;
}

.fade-transform-enter {
  opacity: 0;
  transform: translateX(-30px);
}

.fade-transform-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.vertical-layout {
  flex-direction: column; /* 改为垂直排列 */
  align-items: center;    /* 水平居中 */
  text-align: center;     /* 文字居中 */
  gap: 8px;               /* 元素间距 */
  padding: 12px 0;        /* 上下内边距 */
}
 
.vertical-layout .logo-wrapper {
  margin: 0;              /* 清除原有margin */
  width: 60px;            /* 固定logo宽度 */
  height: 60px;           /* 固定logo高度 */
}
 
.vertical-layout .logo-img {
  max-width: 100%;
  max-height: 100%;
}

</style>
