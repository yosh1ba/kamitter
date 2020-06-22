<template>
  <div>
    <img :src="item.twitter_avatar" alt="">
    <p>{{item.twitter_screen_name}}</p>
    <div v-for="(target, index) in targets">
      <input type="text" v-model="target.screen_name">
      <button v-on:click="deleteForm(index)">削除</button>
      <span>{{target.message}}</span>
    </div>
    <button v-on:click="addForm">追加</button>
    <button v-on:click="saveTargetForm">保存</button>
  </div>

</template>

<script>
  import axios from "axios";
  import {OK} from "../util";

  export default {
    name: "Account",
    props: {
      item: {
        type: Object,
        required: true
      }
    },
    data() {
      return {
        targets: []  // ターゲットアカウント
      }
    },
    methods: {
      addForm() {
        const additionalForm = {
          screen_name: '',
          message:''
        }
        this.targets.push(additionalForm)
      },
      deleteForm(index){
        // クリックした削除ボタンに対応するフォームを削除
        this.targets.splice(index, 1);
      },
      async saveTargetForm(){

        for(let data of this.targets){
          if(data.name !== ''){
            /*
            認証済みアカウントごとにターゲットアカウントリストを作成するため
            twitter_usersテーブル内のidをプロパティとして持たせる
            */
            this.$set(data, 'twitter_user_id', this.item.id)
            const response = await axios.post('/api/twitter/target/check', data)

            // 失敗の場合、エラー内容をストアする(APIステータス自体のエラー判定)
            if(response.status !== OK){
              this.$store.commit('error/setCode', response.status)
              this.$store.commit('error/setMessage', response.data.errors)
              return false
            }

            // レスポンスのデータにエラーが含まれる場合、エラーコードごとに処理を行う
            if(response.data.errors){
              let err = response.data.errors.shift()
              switch (err.code) {
                case 50:
                  this.$set(data, 'message', 'ユーザーが存在しません')
                  return false
              }
            // エラーがない場合はメッセージをクリアする
            }else {
              this.$set(data, 'message', '')
            }
          // フォームが空欄の場合はエラーを表示する
          }else {
            this.$set(data, 'message', 'ユーザーが存在しません')
            return false
          }
        }

        // ユーザーの存在チェック完了後、ターゲットアカウントリストの作成を行う
        const response = await axios.post('/api/twitter/target', this.targets)

        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }

        this.$store.commit('message/setText', 'ターゲットアカウントが保存されました', { root: true })
      },
      async queryTargetForm(){
        // ターゲットアカウントリストの内容を呼び出す
        const response = await axios.get(`/api/twitter/target/${this.item.id}`);

        // ターゲットアカウントリストが存在する場合、フォームに展開する
        if(response.data.length !== 0){
          for (let data of response.data){
            this.targets.push(data)
          }
        }
      }
    },
    created() {
      // ページ表示時にターゲットアカウントリストの内容を呼び出す
      this.queryTargetForm()
    }
  }
</script>

<style scoped>

</style>