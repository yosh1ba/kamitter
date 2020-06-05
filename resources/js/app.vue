<template>
<div>
  <Navbar />
  <RouterView />
  <Footer />
</div>
</template>

<script>
import Navbar from "./components/Navbar";
import Footer from "./components/Footer";
export default {
  components: {
    Footer,
    Navbar
  },

}
</script>

<style>

</style>