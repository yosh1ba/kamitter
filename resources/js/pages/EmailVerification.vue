<template>
  <div class="l-container">
    <div class="c-panel">
      <div class="c-panel__content p-verify__content">
        <h3 class="c-panel__content__header p-verify__content__header">メールアドレス認証</h3>
        <p>メールアドレスの認証中です。そのままお待ち下さい。</p>
      </div>
    </div>
  </div>
</template>

<script>
  import {OK} from "../util";
  import {mapState} from "vuex";

  export default {
    name: "EmailVerification",
    data() {
      return {
        queryURL: ''
      }
    },
    async mounted() {
      const queryURL = this.$route.query.queryURL || '';

      if (queryURL != '') {
        const response = await axios.get(queryURL)

        // 失敗の場合、エラー内容をストアする
        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }

        // ユーザー情報再取得
        await this.$store.dispatch('auth/currentUser');
        
        this.$store.commit('message/setText', 'メールアドレスが認証されました。', { root: true })
        this.$router.push('/mypage')
      }
    },
    // errorストアのmessageステートを、errorMessagesにセット
    computed: {
      ...mapState('error', {
        errorMessages: 'message'
      })
    },
  }
</script>

<style scoped>

</style>