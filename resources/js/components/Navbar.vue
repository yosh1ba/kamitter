<template>
  <div>
    <transition>
      <div class="c-message" v-if="messageText">
        {{ messageText }}
      </div>
    </transition>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
export default {
  name: 'Navbar',
  computed: {
    ...mapState('message', {
      messageText: 'text'
    })
  },
  watch: {
    messageText: function(){
      setTimeout(this.hideMessage,3000)
    }
  },
  methods: {
    hideMessage() {
      this.$store.commit('message/setText', '')
    }
  }

}
</script>

<style scoped>
.v-enter-active, .v-leave-active{
  transition: all 1s;
}
.v-enter, .v-leave-to{
  opacity: 0;
}

.c-message {
  position: absolute;
  top: 0;
}

</style>