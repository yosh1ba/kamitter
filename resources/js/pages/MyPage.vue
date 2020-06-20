<template>
  <div>
    <h1>マイページ</h1>
    <form @submit.prevent="submit">
      <div>
        <button type="submit">twitterアカウント連携</button>
      </div>
    </form>
  </div>
</template>

<script>
  import {OK} from "../util"
  import {mapState} from "vuex";

  export default {
    name: "MyPage",
    methods: {
      async submit() {

        // twitter認証ページのURL取得
        const response = await axios.get('twitter')


        // 失敗の場合、エラー内容をストアする
        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }

        // twitter認証ページへリダイレクト
        window.location = response.data.redirect_url

      }
    },
    watch: {
      'beforeRouteUpdate' (to,from,next){
        console.log(from)
        console.log(to)
        next()
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