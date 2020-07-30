<template>
<div class="l-wrapper">
  <Navbar />
  <RouterView />
  <Footer />
</div>
</template>

<script>
import Navbar from "./components/Navbar";
import Footer from "./components/Footer";
import {INTERNAL_SERVER_ERROR} from "./util";
export default {
  components: {
    Footer,
    Navbar
  },
  computed: {
    // ストア上のエラーコードを返す
    errorCode () {
      return this.$store.state.error.code
    }
  },
  watch: {
    // errorCodeを監視し、INTERNAL_SERVER_ERROR が発生した場合にエラーページへ遷移させる
    errorCode: {
      handler (val) {
        if (val === INTERNAL_SERVER_ERROR) {
          this.$router.push('/500')
        }
      },
      // 初期化時にも実行する
      immediate: true
    },
    // 画面遷移が行われた場合に、ストア上のエラー情報をクリアする
    $route () {
      this.$store.commit('error/setCode', null)
      this.$store.commit('error/setMessage', null)
    }
  }
}
</script>

<style>

</style>