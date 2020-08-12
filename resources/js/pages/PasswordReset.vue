<template>
  <div class="l-container">
    <div class="c-panel">
      <div class="c-panel__content p-reset__content">
        <h3 class="c-panel__content__header p-reset__content__header">パスワードリセット</h3>
        <form @submit.prevent="reset">
          <div v-if="errorMessages" class="c-form__error">
            <ul v-if="errorMessages.email">
              <li v-for="msg in errorMessages.email" :key="msg" class="c-form__error__msg ">{{ msg }}</li>
            </ul>
            <ul v-if="errorMessages.password">
              <li v-for="msg in errorMessages.password" :key="msg" class="c-form__error__msg ">{{ msg }}</li>
            </ul>
          </div>
          <div class="c-form__item p-reset__form__item">
            <label for="mail" class="c-form__item__label p-reset__form__item__label">メールアドレス</label>
            <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email" class="c-form__item__input p-reset__form__item__input">
          </div>
          <div class="c-form__item p-reset__form__item">
            <label for="password" class="c-form__item__label p-reset__form__item__label">新しいパスワード</label>
            <div class="u-position--relative">
              <input id="password" :type="inputType" placeholder="パスワードを入力して下さい" v-model="form.password" class="c-form__item__input p-reset__form__item__input">
              <i class="far fa-eye c-button__eye" @click="onClick" v-if="!isChecked"></i>
              <i class="far fa-eye-slash c-button__eye" @click="onClick" v-if="isChecked"></i>
            </div>
            <label class="c-form__item__label ">※8-15文字の半角英数字</label>
          </div>
          <div class="c-form__item p-reset__form__item">
            <label for="password_confirmation" class="c-form__item__label p-reset__form__item__label">新しいパスワード再確認</label>
            <div class="u-position--relative">
              <input id="password_confirmation" :type="inputTypeConfirm" placeholder="パスワードを再度入力して下さい" v-model="form.password_confirmation" class="c-form__item__input p-reset__form__item__input">
              <i class="far fa-eye c-button__eye" @click="onClickConfirm" v-if="!isCheckedConfirm"></i>
              <i class="far fa-eye-slash c-button__eye" @click="onClickConfirm" v-if="isCheckedConfirm"></i>
            </div>
            <label class="c-form__item__label ">※8-15文字の半角英数字</label>
          </div>
          <button type="submit" class="c-form__btn p-reset__form__btn">再設定する</button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
  import {OK, UNPROCESSABLE_ENTITY} from '../util'
  import {mapState} from "vuex";
export default {
  name: 'PasswordReset',
  data() {
    return {
      form: {
        email: '',                  // リセット対象のメールアドレス
        password: '',               // 新しいパスワード
        password_confirmation: '',  // 新しいパスワード確認
        token: ''                   // パスワードリセット実行するための一時的なトークン
      },
      requestUrl: '',
      isChecked: false,             // パスワード表示切り替え用
      isCheckedConfirm: false       // パスワード(再確認)表示切り替え用
    }
  },
  created() {
    this.setQuery()
  },
  methods: {
    // パスワードリセット用メソッド
    async reset(){
      const response = await axios.post(`/api/password/reset/${this.form.token}`, this.form)

      // 失敗の場合、エラー内容をストアする
      if(response.status !== OK){
        this.$store.commit('error/setCode', response.status)
        this.$store.commit('error/setMessage', response.data.errors)
        return false
      }

      // エラー内容をリセット
      this.resetErrors = null

      this.$store.commit('message/setText', 'パスワードが変更されました。', { root: true })
      this.$router.push('/')
    },
    setQuery() { // getリクエストのパラメータを取得する関数
      this.requestUrl = this.$route.query.queryURL || ''; // パスワードリセットAPIのURL
      this.form.token = this.$route.query.token || '';    // パスワードリセットするために必要なToken
    },
    // パスワードの表示、非表示を切り替えるメソッド
    onClick: function() {
      this.isChecked = !this.isChecked;
    },
    // パスワード(再確認)の表示、非表示を切り替えるメソッド
    onClickConfirm: function() {
      this.isCheckedConfirm = !this.isCheckedConfirm;
    },
  },
  // errorストアのmessageステートを、errorMessagesにセット
  computed: {
    ...mapState('error', {
      errorMessages: 'message'
    }),
    inputType: function () {
      return this.isChecked ? "text" : "password"
    },
    inputTypeConfirm: function () {
      return this.isCheckedConfirm ? "text" : "password"
    }
  },

}
</script>

<style>

</style>