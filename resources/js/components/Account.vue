<template>
  <div>
    <img :src="item.twitter_avatar" alt="">
    <p>{{item.twitter_screen_name}}</p>
    <div v-for="(target, index) in targets">
      <input type="text" v-model="target.screen_name">
      <button v-on:click="deleteTargetForm(index)">削除</button>
      <span>{{target.message}}</span>
    </div>
    <button v-on:click="addTargetForm">追加</button>
    <button v-on:click="saveTargetForm">保存</button>
    <div v-for="(keyword, index) in keywords">
      <select v-model="keyword.selected">
        <option v-for="option in keyword.options">
          {{ option }}
        </option>
      </select>
      <input type="text" v-model="keyword.text">
      <button v-on:click="deleteKeywordForm(index)">削除</button>
      <span>{{keyword.message}}</span>
    </div>
    <button v-on:click="addKeywordForm">追加</button>
    <button v-on:click="saveKeywordForm">保存</button>
  </div>


</template>

<script>
  import axios from "axios";
  import {OK} from "../util";
  // import vSelect from 'vue-select'

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
        targets: [],  // ターゲットアカウント
        keywords: []  // キーワード
      }
    },
    methods: {
      addTargetForm() {
        const additionalForm = {
          screen_name: '',
          message:''
        }
        this.targets.push(additionalForm)
      },
      deleteTargetForm(index){
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
      },
      addKeywordForm() {
        const additionalForm = {
          selected: 'ADD', // セレクトボックスの結果が入る(規定値：ADD)
          text:'',  // 検索キーワード
          message: '',  // エラーメッセージ
          options: [  // セレクトボックスの選択肢
            'ADD',
            'OR',
            'NOT'
          ]
        }
        this.keywords.push(additionalForm)
      },
      deleteKeywordForm(index){
        // クリックした削除ボタンに対応するフォームを削除
        this.keywords.splice(index, 1);
      },
      async saveKeywordForm(){
        for(let data of this.keywords){
          // フォームに入力がある場合はエラーをクリア
          if(data.text !== ''){
            this.$set(data, 'message', '')
          // フォームが空欄の場合はエラーを表示する
          }else {
            this.$set(data, 'message', 'キーワードが存在しません')
            return false
          }
        }

        // フォームの入力チェック完了後、フォロワーサーチキーワードリストの作成を行う
        const response = await axios.post('/api/twitter/keyword', this.keywords)

        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }

        this.$store.commit('message/setText', 'キーワードが保存されました', { root: true })
      },
    },
    created() {
      // ページ表示時にターゲットアカウントリストの内容を呼び出す
      this.queryTargetForm()
    }
  }
</script>

<style scoped>

</style>