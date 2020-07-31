<template>
  <div class="l-container">
    <div class="l-main">
      <div class="c-panel p-signup">
        <div class="c-panel__content p-signup__content">
          <loading :active.sync="isLoading"
                   :is-full-page="fullPage">
          </loading>
          <h3 class="c-panel__content__header p-signup__content__header">新規登録</h3>
          <form @submit.prevent="register">
            <div v-if="registerErrors" class="c-form__error p-signup__form__error">
              <ul v-if="registerErrors.name">
                <li v-for="msg in registerErrors.name" :key="msg" class="c-form__error__msg p-signup__form__error__msg">{{ msg }}</li>
              </ul>
              <ul v-if="registerErrors.email">
                <li v-for="msg in registerErrors.email" :key="msg" class="c-form__error__msg p-signup__form__error__msg">{{ msg }}</li>
              </ul>
              <ul v-if="registerErrors.password">
                <li v-for="msg in registerErrors.password" :key="msg" class="c-form__error__msg p-signup__form__error__msg">{{ msg }}</li>
              </ul>
            </div>
            <div class="c-form__item p-signup__form__item">
              <label for="name" class="c-form__item__label p-signup__form__item__label"></label>
              <input id="name" type="text" placeholder="ユーザー名" v-model="form.name" class="c-form__item__input p-signup__form__item__input">
            </div>
            <div class="c-form__item p-signup__form__item">
              <label for="mail" class="c-form__item__label p-signup__form__item__label"></label>
              <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email" class="c-form__item__input p-signup__form__item__input">
            </div>
            <div class="c-form__item p-signup__form__item">
              <label for="password" class="c-form__item__label p-signup__form__item__label"></label>
              <input id="password" type="password" placeholder="パスワード" v-model="form.password" class="c-form__item__input p-signup__form__item__input">
            </div>
            <div class="c-form__item p-signup__form__item">
              <label for="password_confirmation" class="c-form__item__label p-signup__form__item__label"></label>
              <input id="password_confirmation" type="password" placeholder="パスワード再確認" v-model="form.password_confirmation" class="c-form__item__input p-signup__form__item__input">
            </div>
            <button type="submit" class="c-form__btn p-signup__form__btn">登録する</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapState} from "vuex";
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';

export default {
  name: "Signup",
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      isLoading: false,
      fullPage: true
    }
  },
  components: {
    Loading
  },
  methods: {
    // ユーザー登録用メソッド
    async register() {
      this.isLoading = true;
      // authストアのユーザ登録用メソッドを呼び出す
      await this.$store.dispatch('auth/register', this.form)
      this.isLoading = false;
      // 成功の場合（ユーザ登録が正常に行われた場合）
      if (this.apiStatus) {

        // トップページに移動する
        this.$router.push('/')
      }
    },

    // エラー情報をクリアするメソッド
    clearError(){
      this.$store.commit('auth/setRegisterErrorMessages', null)
    }
  },
  computed: {
    ...mapState({
      // APIのレスポンスが正常かどうかを判断
      apiStatus: state => state.auth.apiStatus,
      // 登録時のエラーメッセージを取得
      registerErrors: state => state.auth.registerErrorMessages
    })
  },
  // ページ生成時にエラー情報をクリアする
  created() {
    this.clearError()
  }

}
</script>

<style>

</style>