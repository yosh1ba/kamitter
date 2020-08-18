<template>
  <div class="l-container">
    <div class="l-main">
      <div class="p-hero">
        <div class="p-hero__content">
          <h2 class="p-hero__content__header">あなたのTwitter運用を1クリックで。</h2>
          <p class="p-hero__content__detail">神ったーは、完全無料のTwitter自動運用サービスです</p>
          <router-link class="p-hero__content__link" to="/signup">使ってみる</router-link>
        </div>
      </div>
      <About></About>
    </div>
  </div>
</template>

<script>
import About from "../components/About";

export default {
  name: "Home",
  components: {
    About
  },
}
</script>

<style>

</style>