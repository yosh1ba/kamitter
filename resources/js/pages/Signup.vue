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
              <label for="name" class="c-form__item__label p-signup__form__item__label"><span class="c-button--require">必須</span>ユーザー名</label>
              <input id="name" type="text" placeholder="例）ウェブ太郎" v-model="form.name" class="c-form__item__input p-signup__form__item__input" required aria-required="true">
              <label class="c-form__item__label--under">※10文字以下</label>
            </div>
            <div class="c-form__item p-signup__form__item">
              <label for="mail" class="c-form__item__label p-signup__form__item__label"><span class="c-button--require">必須</span>メールアドレス</label>
              <input id="mail" type="email" placeholder="例）test@test.com" v-model="form.email" class="c-form__item__input p-signup__form__item__input" required aria-required="true">
            </div>
            <div class="c-form__item p-signup__form__item">
              <label for="password" class="c-form__item__label p-signup__form__item__label"><span class="c-button--require">必須</span>パスワード</label>
              <div class="u-position--relative">
                <input id="password" :type="inputType" placeholder="パスワードを入力して下さい" v-model="form.password" class="c-form__item__input p-signup__form__item__input" required aria-required="true">
                <i class="far fa-eye c-button__eye" @click="onClick" v-if="!isChecked"></i>
                <i class="far fa-eye-slash c-button__eye" @click="onClick" v-if="isChecked"></i>
              </div>
              <label class="c-form__item__label--under">※8-15文字の半角英数字</label>
            </div>
            <div class="c-form__item p-signup__form__item">
              <label for="password_confirmation" class="c-form__item__label p-signup__form__item__label"><span class="c-button--require">必須</span>パスワード再確認</label>
              <div class="u-position--relative">
                <input id="password_confirmation" :type="inputTypeConfirm" placeholder="パスワードを再度入力して下さい" v-model="form.password_confirmation" class="c-form__item__input p-signup__form__item__input" required aria-required="true">
                <i class="far fa-eye c-button__eye" @click="onClickConfirm" v-if="!isCheckedConfirm"></i>
                <i class="far fa-eye-slash c-button__eye" @click="onClickConfirm" v-if="isCheckedConfirm"></i>
              </div>
              <label class="c-form__item__label--under">※8-15文字の半角英数字</label>
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
      fullPage: true,
      isChecked: false,             // パスワード表示切り替え用
      isCheckedConfirm: false       // パスワード(再確認)表示切り替え用
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

    // パスワードの表示、非表示を切り替えるメソッド
    onClick: function() {
      this.isChecked = !this.isChecked;
    },
    // パスワード(再確認)の表示、非表示を切り替えるメソッド
    onClickConfirm: function() {
      this.isCheckedConfirm = !this.isCheckedConfirm;
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
    }),
    inputType: function () {
      return this.isChecked ? "text" : "password"
    },
    inputTypeConfirm: function () {
      return this.isCheckedConfirm ? "text" : "password"
    }
  },
  // ページ生成時にエラー情報をクリアする
  created() {
    this.clearError()
  }

}
</script>

<style>

</style>