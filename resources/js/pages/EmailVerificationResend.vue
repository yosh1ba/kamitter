<template>
  <div class="l-container">
    <div class="c-panel">
      <div class="c-panel__content p-resend__content">
        <p>メールアドレスの認証が行われていません。</p>
        <p>以下のボタンをクリックし、認証を行って下さい。</p>
        <form @submit.prevent="resend">
          <button type="submit" class="c-form__btn p-resend__form__btn">認証メール送信</button>
        </form>
      </div>
    </div>
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