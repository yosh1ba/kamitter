<template>
  <div>
    <h1>パスワード再設定
    </h1>
    <form @submit.prevent="send">
      <input id="mail" type="email" placeholder="メールアドレス" v-model="form.email">
      <div>
        <button type="submit">送信</button>
      </div>
    </form>
  </div>
</template>

<script>
import {OK} from '../util'
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

      // 失敗の場合、エラーコードをストアする
      if(response.status !== OK){
        this.$store.commit('error/setCode', response.status)
        return false
      }
      this.$store.commit('message/setText', 'パスワード再発行メールを送信しました。', { root: true })
      this.$router.push('/')
    }
  }
}
</script>

<style>

</style>