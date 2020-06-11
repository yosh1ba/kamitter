<template>
  <div>
    <h1>メールアドレス認証</h1>
    <p>メールアドレスの認証中です。そのままお待ち下さい。</p>
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