<template>
  <div>
    <h1>パスワードリセット</h1>
    <form @submit.prevent="reset">
      <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email">
      <input id="password" type="password" placeholder="パスワード" v-model="form.password">
      <input id="password_confirmation" type="password" placeholder="パスワード再確認" v-model="form.password_confirmation">
      <div>
        <button type="submit">再設定</button>
      </div>
    </form>
  </div>
</template>

<script>
import {OK} from '../util'
export default {
  name: 'PasswordReset',
  data() {
    return {
      form: {
        email: '',                  // リセット対象のメールアドレス
        password: '',               // 新しいパスワード
        password_confirmation: '',  // 新しいパスワード確認
        token: ''                   // パスワードリセット実行するための一時的なトークン
      },
      requestUrl: ''
    }
  },
  created() {
    this.setQuery()
  },
  methods: {
    async reset(){
      const response = await axios.post(`/api/password/reset/${this.form.token}`, this.form)

      // 失敗の場合、エラーコードをストアする
      if(response.status !== OK){
        this.$store.commit('error/setCode', response.status)
        return false
      }
      this.$store.commit('message/setText', 'パスワードが変更されました。', { root: true })
      this.$router.push('/')
    },
    setQuery() { // getリクエストのパラメータを取得する関数
      this.requestUrl = this.$route.query.queryURL || ''; // パスワードリセットAPIのURL
      this.form.token = this.$route.query.token || '';    // パスワードリセットするために必要なToken
    },
  }

}
</script>

<style>

</style>