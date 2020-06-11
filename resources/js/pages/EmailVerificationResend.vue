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
  import {mapState} from "vuex";

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

        // 認証用メールを再送信する
        const response = await axios.post('/api/email/resend', this.form)

        // 失敗の場合、エラー内容をストアする
        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }
        this.$store.commit('message/setText', '認証メールを送信しました。', { root: true })
        this.$router.push('/')
      }
    },
    // errorストアのmessageステートを、errorMessagesにセット
    computed: {
      ...mapState('error', {
        errorMessages: 'message'
      })
    },
    created() {
      // すでに認証済みの場合は、マイページへリダイレクトする
      if(this.$store.getters["auth/verified"]){
        this.$router.push('/mypage')
      }
    }
  }
</script>

<style scoped>

</style>