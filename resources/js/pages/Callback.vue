<template>
  <p>twitterとの連携を行っています。</p>
</template>

<script>
  import {OK} from "../util";

  export default {
    name: "Callback",
    async created() {

      // twitterとの連携を行う
      const response = await axios.get('/twitter/register', { params: this.$route.query })

      // 失敗の場合、エラー内容をストアする
      if(response.status !== OK){
        this.$store.commit('error/setCode', response.status)
        this.$store.commit('error/setMessage', response.data.errors)
        return false
      }

      // マイページへ遷移する
      this.$router.push('/mypage')
    }
  }
</script>

<style scoped>

</style>