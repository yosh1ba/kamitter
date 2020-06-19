import Vue from 'vue'
import VueRouter from 'vue-router'

// auth ストアを使用するため追加
import store from './store'

// ページコンポーネントをインポートする
import Home from './pages/Home.vue'
import Login from './pages/Login.vue'
import Signup from './pages/Signup.vue'
import PasswordForget from './pages/PasswordForget'
import PasswordReset from './pages/PasswordReset'
import EmailVerification from './pages/EmailVerification'
import EmailVerificationResend from "./pages/EmailVerificationResend"
import MyPage from './pages/MyPage'
import System from "./pages/errors/System";
import Callback from "./pages/Callback";


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
    component: Login,
    // ログイン状態の場合、ルートディレクトリへ遷移
    beforeEnter (to, from, next) {
      if (store.getters['auth/check']) {
        next('/')
      } else {
        next()
      }
    }
  },
  {
    path: '/signup',
    component: Signup,
    // ログイン状態の場合、ルートディレクトリへ遷移
    beforeEnter (to, from, next) {
      if (store.getters['auth/check']) {
        next('/')
      } else {
        next()
      }
    }
  },
  {
    path: '/password/forget',
    component: PasswordForget,
    // ログイン状態の場合、ルートディレクトリへ遷移
    beforeEnter (to, from, next) {
      if (store.getters['auth/check']) {
        next('/')
      } else {
        next()
      }
    }
  },
  {
    path: '/password/reset',
    component: PasswordReset,
    // ログイン状態の場合、ルートディレクトリへ遷移
    beforeEnter (to, from, next) {
      if (store.getters['auth/check']) {
        next('/')
      } else {
        next()
      }
    }
  },
  {
    path: '/email/verification',
    component: EmailVerification,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/email/verification/resend',
    component: EmailVerificationResend,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/mypage',
    component: MyPage,
    meta: {
      requiresVerify: true
    }
  },
  {
    path: '/twitter/callback',
    component: Callback,
    meta: {
      requiresVerify: true
    }
  },
  {
    path: '/500',
    component: System
  }
]

// VueRouterインスタンスを作成する
const router = new VueRouter({
  mode: 'history',    // URLに # を付与しないための設定
  routes
})



// ログイン状態によって画面遷移をコントロールする
router.beforeEach((to, from, next) => {

  // ログインが必要な画面の場合
  if( to.matched.some(record => record.meta.requiresAuth)){
    // ログイン状態を判定し、ログインしている場合、そのまま遷移させる
    if(store.getters['auth/check']){
      next(
        next()
      )
    // ログインしていない場合、ログイン画面へリダイレクトさせる
    }else{
      next({
        path: '/login',
        query: {redirect: to.fullPath}
      })
    }
  // ログインが不要な場合、そのまま遷移させる
  }else {
    next()
  }
  
  // メールアドレス認証が必要な画面の場合
  if( to.matched.some(record => record.meta.requiresVerify)){
    // ログイン状態を判定し、ログインしている場合、そのまま遷移させる
    if(store.getters['auth/verified']){
      next(
        next()
      )
      // ログインしていない場合、ログイン画面へリダイレクトさせる
    }else{
      next({
        path: '/email/verification/resend',
        query: {redirect: to.fullPath}
      })
    }
    // ログインが不要な場合、そのまま遷移させる
  }else {
    next()
  }
})

// VueRouterインスタンスをエクスポートする
// app.jsでインポートするため
export default router
