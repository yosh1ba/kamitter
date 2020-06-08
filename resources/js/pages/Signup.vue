<template>
  <div>
    <h1>新規登録</h1>
      <form @submit.prevent="register">
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
    }
  },
  computed: {
    // APIのレスポンスが正常かどうかを判断
    apiStatus () {
      return this.$store.state.auth.apiStatus
    },
  }

}
</script>

<style>

</style>