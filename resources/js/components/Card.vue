<template>
  <div class="p-card">
    <span class="p-card__icon"><i :class=card.icon></i></span>
    <h4 class="p-card__header">{{card.title}}</h4>
    <span class="p-card__description">{{card.description}}</span>
  </div>
</template>

<script>
  export default {
    name: "Card",
    props: {
      card: {
        type: Object,
        required: true
      }
    }
  }
</script>

<style scoped>

</style>