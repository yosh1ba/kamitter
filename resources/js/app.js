import './bootstrap'
import Vue from 'vue'
import router from './router' // ルーティングの定義をインポートする
import App from './App.vue' // ルートコンポーネントをインポートする
import store from './store'

const createApp = async () =>{
  await store.dispatch('auth/currentUser');
  
  new Vue({
    el: '#app',
    router, // ルーティングの定義を読み込む
    store,  // Vuexストアを読み込む
    components: { App }, // ルートコンポーネントの使用を宣言する
    template: '<App />' // ルートコンポーネントを描画する
  })

}

// SyncManager(store, router);

createApp();
