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
            <label for="mail" class="c-form__item__label p-reset__form__item__label"></label>
            <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email" class="c-form__item__input p-reset__form__item__input">
          </div>
          <div class="c-form__item p-reset__form__item">
            <label for="password" class="c-form__item__label p-reset__form__item__label"></label>
            <input id="password" type="password" placeholder="パスワード" v-model="form.password" class="c-form__item__input p-reset__form__item__input">
          </div>
          <div class="c-form__item p-reset__form__item">
            <label for="password_confirmation" class="c-form__item__label p-reset__form__item__label"></label>
            <input id="password_confirmation" type="password" placeholder="パスワード再確認" v-model="form.password_confirmation" class="c-form__item__input p-reset__form__item__input">
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
      requestUrl: ''
    }
  },
  created() {
    this.setQuery()
  },
  methods: {
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
    }
  },
  // errorストアのmessageステートを、errorMessagesにセット
  computed: {
    ...mapState('error', {
      errorMessages: 'message'
    })
  },

}
</script>

<style>

</style>