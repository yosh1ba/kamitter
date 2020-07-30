<template>
  <div class="l-container">
    <div class="l-main">
      <div class="c-panel p-mypage">
        <div class="c-panel__content p-mypage__content">
          <h3 class="c-panel__content__header p-mypage__content__header">マイページ</h3>
          <Account
            v-for="account in accounts"
            :key="account.id"
            :item="account"
          />
          <form @submit.prevent="submit">
            <button type="submit" class="c-form__btn p-mypage__form__btn">Twitterアカウント連携</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import Account from '../components/Account'
  import {OK} from "../util"
  import {mapState} from "vuex";
  import axios from "axios";

  export default {
    name: "MyPage",
    data() {
      return {
        accounts: []
      }
    },
    components: {
      Account
    },
    methods: {
      async submit() {
        if(this.accounts.length > 3){
          alert('登録できるアカウントは最大10個です')
          return false
        }

        // twitter認証ページのURL取得
        const response = await axios.get('twitter')

        // 失敗の場合、エラー内容をストアする
        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }
        // twitter認証ページへリダイレクト
        window.location = response.data.redirect_url
      },
      async fetchAccounts(){
        const response = await axios.get(`/api/twitter/user/${this.$store.getters['auth/userid']}`)
        this.accounts = response.data
        // console.log(this.accounts)
      }
    },
    watch: {
      $route: {
        async handler(){
          await this.fetchAccounts()
        },
        immediate: true // 初期読み込み時にも呼び出す
      },
    },
    computed: {
      ...mapState({
        // APIのレスポンスが正常かどうかを判断
        apiStatus: state => state.twitter.apiStatus,
      })
    },
  }
</script>

<style scoped>

</style>