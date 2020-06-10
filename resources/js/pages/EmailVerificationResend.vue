<template>
  <div>
    <form @submit.prevent="resend">
      <p>メールアドレスの認証が行われていません。</p>
      <p>以下のボタンをクリックし、認証を行って下さい。</p>
      <div>
        <button type="submit">認証メール送信</button>
      </div>
    </form>
  </div>
</template>

<script>
  import {OK} from "../util";

  export default {
    name: "EmailVerificationResend",
    data() {
      return {
        form: {
          email: ''
        }
      }
    },
    methods: {
      async resend(){

        // ログインユーザーのメールアドレスを取得
        this.form.email = this.$store.getters["auth/email"]

        const response = await axios.post('/api/email/resend', this.form)

        // 失敗の場合、エラーコードをストアする
        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          return false
        }
        this.$store.commit('message/setText', '認証メールを送信しました。', { root: true })
        await this.$router.push('/')
      }
    }
  }
</script>

<style scoped>

</style>