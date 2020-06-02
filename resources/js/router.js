import Vue from 'vue'
import VueRouter from 'vue-router'

// ページコンポーネントをインポートする
import Home from './pages/Home.vue'
import Login from './pages/Login.vue'

// VueRouterプラグインを使用する
// これによって<RouterView />コンポーネントなどを使うことができる
Vue.use(VueRouter)

// パスとコンポーネントのマッピング
const routes = [
    {
        path: '/',
        component: Home
    },
    {
        path: '/login',
        component: Login
    }
]

// VueRouterインスタンスを作成する
const router = new VueRouter({
    mode: 'history',    // URLに # を付与しないための設定
    routes

})

// VueRouterインスタンスをエクスポートする
// app.jsでインポートするため
export default router