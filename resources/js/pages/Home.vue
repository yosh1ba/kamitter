<template>
  <div>
    <h1>てすと</h1>
    <button v-on:click="logout">ログアウト</button>
  </div>
</template>

<script>
export default {
  name: "Home",
  methods: {
    async logout(){
      // authストアのログアウト用メソッドを呼び出す
      await this.$store.dispatch('auth/logout', this.form)

      // 成功の場合（ユーザ登録が正常に行われた場合）
      if (this.apiStatus) {

        // トップページに移動する
        this.$router.push('/')
      }
    }
  }

}
</script>

<style>

</style>