<template>
  <header class="l-container l-header">
    <transition>
      <div class="c-flash" v-if="messageText">
        {{ messageText }}
      </div>
    </transition>
    <div class="p-header">
      <div class="l-flex p-header__content">
        <h2 class="p-header__logo">神ったー</h2>
        <nav class="p-header__nav" v-if="isLogin">
          <ul class="l-flex p-nav">
            <router-link tag="li" class="p-nav__item" to="/mypage"><a>マイページ</a></router-link>
            <li class="p-nav__item" v-on:click="logout"><a>ログアウト</a></li>
          </ul>
        </nav>
        <nav class="p-header__nav" v-else>
          <ul class="l-flex p-nav">
            <router-link tag="li" class="p-nav__item" to="/signup"><a>新規登録</a></router-link>
            <router-link tag="li" class="p-nav__item" to="/login"><a>ログイン</a></router-link>
          </ul>
        </nav>
      </div>
    </div>

  </header>
</template>

<script>
  import {mapState, mapActions, mapGetters} from 'vuex';
  export default {
    name: 'Navbar',
    computed: {
      ...mapState('message', {
        messageText: 'text'
      }),
      ...mapGetters({
        isLogin: 'auth/check',
        userid: 'auth/userid'
      }),
    },
    watch: {
      messageText: function(){
        setTimeout(this.hideMessage,3000)
      }
    },
    methods: {
      // フラッシュメッセージ
      hideMessage() {
        this.$store.commit('message/setText', '')
      },
      // ログアウト
      async logout(){
        // authストアのログアウト用メソッドを呼び出す
        await this.$store.dispatch('auth/logout', this.form)

        await this.$store.commit('message/setText', 'ログアウトが完了しました', { root: true })
        this.$router.push('/')
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

</style>
