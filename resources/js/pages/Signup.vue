<template>
  <div>
    <h1>新規登録</h1>
      <form @submit.prevent="register">
        <div v-if="registerErrors" class="">
          <ul v-if="registerErrors.name">
            <li v-for="msg in registerErrors.name" :key="msg" class="">{{ msg }}</li>
          </ul>
          <ul v-if="registerErrors.email">
            <li v-for="msg in registerErrors.email" :key="msg" class="">{{ msg }}</li>
          </ul>
          <ul v-if="registerErrors.password">
            <li v-for="msg in registerErrors.password" :key="msg" class="">{{ msg }}</li>
          </ul>
        </div>
        <input id="name" type="text" placeholder="ユーザー名" v-model="form.name">
        <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email">
        <input id="password" type="password" placeholder="パスワード" v-model="form.password">
        <input id="password_confirmation" type="password" placeholder="パスワード再確認" v-model="form.password_confirmation">
        <div>
          <button type="submit">ログイン</button>
        </div>
      </form>
  </div>
</template>

<script>
import {mapState} from "vuex";

export default {
  name: "Signup",
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      }
    }
  },
  methods: {
    async register() {
      // authストアのユーザ登録用メソッドを呼び出す
      await this.$store.dispatch('auth/register', this.form)

      // 成功の場合（ユーザ登録が正常に行われた場合）
      if (this.apiStatus) {

        // トップページに移動する
        this.$router.push('/')
      }
    },
    // エラー情報をクリアするメソッド
    clearError(){
      this.$store.commit('auth/setRegisterErrorMessages', null)
    }
  },
  computed: {
    ...mapState({
      // APIのレスポンスが正常かどうかを判断
      apiStatus: state => state.auth.apiStatus,
      // 登録時のエラーメッセージを取得
      registerErrors: state => state.auth.registerErrorMessages
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