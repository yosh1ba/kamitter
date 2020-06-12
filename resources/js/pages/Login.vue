<template>
  <div>
    <h1>ログイン</h1>
    <form @submit.prevent="login">
      <div v-if="loginErrors" class="">
        <ul v-if="loginErrors.email">
          <li v-for="msg in loginErrors.email" :key="msg" class="">{{ msg }}</li>
        </ul>
        <ul v-if="loginErrors.password">
          <li v-for="msg in loginErrors.password" :key="msg" class="">{{ msg }}</li>
        </ul>
      </div>
      <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email">
      <input id="password" type="password" placeholder="パスワード" v-model="form.password">
      <p>パスワードを忘れた方は <router-link to="/password/forget">こちら</router-link></p>
      <div>
        <button type="submit">ログイン</button>
      </div>
    </form>
  </div>
</template>

<script>
import {mapState} from "vuex";

export default {
  name: 'Login',
  data() {
    return {
      form: {
        email: '',
        password: '',
      }
    }
  },
  methods: {
    async login() {
      // authストアのログイン用メソッドを呼び出す
      await this.$store.dispatch('auth/login', this.form)

      // 成功の場合（ユーザ登録が正常に行われた場合）
      if (this.apiStatus) {

        // トップページに移動する
        this.$router.push('/')
      }
    },
    // エラー情報をクリアするメソッド
    clearError(){
      this.$store.commit('auth/setLoginErrorMessages', null)
    }
  },
  computed: {
    ...mapState({
      // APIのレスポンスが正常かどうかを判断
      apiStatus: state => state.auth.apiStatus,
      // ログイン時のエラーメッセージを取得
      loginErrors: state => state.auth.loginErrorMessages
    })
  },
  // ページ生成時にエラー情報をクリアする
  created() {
    this.clearError()
  }
}
</script>

<style>

</style>