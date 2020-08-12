<template>
  <div class="l-container">
    <div class="l-main">
      <div class="c-panel p-login">
        <div class="c-panel__content p-login__content">
          <h3 class="c-panel__content__header p-login__content__header">ログイン</h3>
          <form @submit.prevent="login">
            <div v-if="loginErrors" class="c-form__error p-login__form__error">
              <ul v-if="loginErrors.email">
                <li v-for="msg in loginErrors.email" :key="msg" class="c-form__error__msg p-login__form__error__msg">{{ msg }}</li>
              </ul>
              <ul v-if="loginErrors.password">
                <li v-for="msg in loginErrors.password" :key="msg" class="c-form__error__msg p-login__form__error__msg">{{ msg }}</li>
              </ul>
            </div>
            <div class="c-form__item p-login__form__item">
              <label for="mail" class="c-form__item__label p-login__form__item__label">メールアドレス</label>
              <input id="mail" type="email" placeholder="メールアドレスを入力して下さい" v-model="form.email" class="c-form__item__input p-login__form__item__input" required aria-required="true">
            </div>
            <div class="p-login__form__item">
              <label for="password" class="c-form__item__label p-login__form__item__label">パスワード</label>
              <div class="u-position--relative">
                <input id="password" :type="inputType" placeholder="パスワードを入力して下さい" v-model="form.password" class="c-form__item__input p-login__form__item__input" required aria-required="true">
                <i class="far fa-eye c-button__eye" @click="onClick" v-if="!isChecked"></i>
                <i class="far fa-eye-slash c-button__eye" @click="onClick" v-if="isChecked"></i>
              </div>
            </div>
            <button type="submit" class="c-form__btn p-login__form__btn">ログイン</button>
            <p>パスワードを忘れた方は <router-link to="/password/forget">こちら</router-link></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapState} from "vuex";

export default {
  name: 'Login',
  data() {
    return {
      form: {
        email: '',
        password: '',
      },
      isChecked: false,
    }
  },
  methods: {
    // ログイン用メソッド
    async login() {
      // authストアのログイン用メソッドを呼び出す
      await this.$store.dispatch('auth/login', this.form)

      // 成功の場合（ユーザ登録が正常に行われた場合）
      if (this.apiStatus) {

        // トップページに移動する
        this.$router.push('/mypage')
      }
    },
    onClick: function() {
      this.isChecked = !this.isChecked;
    },

    // エラー情報をクリアするメソッド
    clearError(){
      this.$store.commit('auth/setLoginErrorMessages', null)
    }
  },
  computed: {
    ...mapState({
      // APIのレスポンスが正常かどうかを判断
      apiStatus: state => state.auth.apiStatus,
      // ログイン時のエラーメッセージを取得
      loginErrors: state => state.auth.loginErrorMessages
    }),
    inputType: function () {
      return this.isChecked ? "text" : "password"
    },
  },

  // ページ生成時にエラー情報をクリアする
  created() {
    this.clearError()
  }
}
</script>

<style>

</style>