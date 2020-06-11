<template>
  <div>
    <h1>パスワード再設定
    </h1>
    <form @submit.prevent="send">
      <div v-if="errorMessages" class="">
        <ul v-if="errorMessages.email">
          <li v-for="msg in errorMessages.email" :key="msg" class="">{{ msg }}</li>
        </ul>
      </div>
      <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email">
      <div>
        <button type="submit">送信</button>
      </div>
    </form>
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