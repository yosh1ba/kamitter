<template>
  <div class="l-container">
    <div class="c-panel">
      <div class="c-panel__content p-callback__content">
        <p>twitterとの連携を行っています。連携完了後、マイページへ移動します。</p>
      </div>
    </div>
  </div>
</template>

<script>
  import {OK} from "../util";
  import {mapState} from "vuex";

  export default {
    name: "Callback",
    async created() {

      // twitterとの連携を行う
      await this.$store.dispatch('twitter/auth', this.$route.query)

      // 成功の場合（ユーザ登録が正常に行われた場合）
      if (this.apiStatus) {

        await this.$store.dispatch('twitter/authenticatedUser', this.$store.getters['auth/userid'])
        // マイページに移動する
        this.$router.push('/mypage')
      }

    },
    computed: {
      ...mapState({
        // APIのレスポンスが正常かどうかを判断
        apiStatus: state => state.twitter.apiStatus,
      })
    },

  }
</script>

<style scoped>

</style>