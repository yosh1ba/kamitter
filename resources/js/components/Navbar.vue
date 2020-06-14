<template>
  <header>
    <transition>
      <div class="c-message" v-if="messageText">
        {{ messageText }}
      </div>
    </transition>
    <h1>神ったー</h1>
    <nav v-if="isLogin">
      <ul>
        <router-link tag="li" to="/mypage"><a>マイページ</a></router-link>
        <li v-on:click="logout"><a>ログアウト</a></li>
      </ul>
    </nav>
    <nav v-else>
      <ul>
        <router-link tag="li" to="/login"><a>ログイン</a></router-link>
      </ul>
    </nav>

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

        // 成功の場合（ユーザ登録が正常に行われた場合）
        if (this.apiStatus) {

          // トップページに移動する
          this.$router.push('/')
        }
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
