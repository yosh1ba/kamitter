<template>
  <div class="l-container">
    <div class="l-main">
      <div class="c-panel">
        <div class="c-panel__content p-forget__content">
          <h3 class="c-panel__content__header p-forget__content__header">パスワード再設定</h3>
          <form @submit.prevent="send">
            <div v-if="errorMessages" class="c-form__error">
              <ul v-if="errorMessages.email">
                <li v-for="msg in errorMessages.email" :key="msg" class="c-form__error__msg ">{{ msg }}</li>
              </ul>
            </div>
            <div class="c-form__item p-forget__form__item">
              <label for="mail" class="c-form__item__label p-forget__form__item__label"></label>
              <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email" class="c-form__item__input ">
            </div>
            <button type="submit" class="c-form__btn p-forget__form__btn">再設定用メールを送信する</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {OK} from '../util'
import {mapState} from "vuex";
export default {
  name: 'PasswordForget',
  data(){
    return {
      form: {
        email: ''
      }
    }
  },
  methods: {
    async send(){
      const response = await axios.post('/api/password/email',this.form);

      console.log(response);

      // 失敗の場合、エラー内容をストアする
      if(response.status !== OK){
        this.$store.commit('error/setCode', response.status)
        this.$store.commit('error/setMessage', response.data.errors)
        return false
      }
      this.$store.commit('message/setText', 'パスワード再発行メールを送信しました。', { root: true })
      this.$router.push('/')
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