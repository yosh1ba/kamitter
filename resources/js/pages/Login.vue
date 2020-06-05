<template>
  <div>
    <h1>ログイン</h1>
    <form @submit.prevent="login">
      <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email">
      <input id="password" type="password" placeholder="パスワード" v-model="form.password">
      <div>
        <button type="submit">ログイン</button>
      </div>
    </form>
  </div>
</template>

<script>
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