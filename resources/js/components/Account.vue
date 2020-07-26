<template>
  <div>
    <img :src="item.twitter_avatar" alt="">
    <p>{{item.twitter_screen_name}}</p>
    <button v-on:click="autoFollow">自動フォロー</button>
    <button v-on:click="autoUnfollow">自動アンフォロー</button>
    <button v-on:click="autoFavorite">自動いいね</button>
    <button v-on:click="deleteUser">認証解除</button>
    <button v-on:click="sendMail">メール送信</button>
    <div v-for="(target, index) in targets">
      <input type="text" v-model="target.screen_name">
      <button v-on:click="deleteTargetForm(index)">削除</button>
      <span>{{target.message}}</span>
    </div>
    <button v-on:click="addTargetForm">追加</button>
    <button v-on:click="saveTargetForm">保存</button>
    <div v-for="(searchKeyword, index) in searchKeywords" v-if="!emptySearchKeyword">
      <select v-model="searchKeyword.selected">
        <option v-for="option in searchKeyword.options">
          {{ option }}
        </option>
      </select>
      <input type="text" v-model="searchKeyword.text">
      <button v-on:click="deleteSearchKeywordForm(index)">削除</button>
      <span>{{searchKeyword.message}}</span>
    </div>
    <button v-on:click="addSearchKeywordForm">追加</button>
    <button v-on:click="saveSearchKeywordForm">保存</button>
    <div v-for="(favoriteKeyword, index) in favoriteKeywords" v-if="!emptyFavoriteKeyword">
      <select v-model="favoriteKeyword.selected">
        <option v-for="option in favoriteKeyword.options">
          {{ option }}
        </option>
      </select>
      <input type="text" v-model="favoriteKeyword.text">
      <button v-on:click="deleteFavoriteKeywordForm(index)">削除</button>
      <span>{{favoriteKeyword.message}}</span>
    </div>
    <button v-on:click="addFavoriteKeywordForm">追加</button>
    <button v-on:click="saveFavoriteKeywordForm">保存</button>
    <div>
      <flat-pickr v-model="reserve.reserved_at" :config="config"></flat-pickr>
      <div>
        <textarea name="" id="" cols="30" rows="10" v-model="reserve.tweet"></textarea>
      </div>
      <button v-on:click="reserveTweet">予約</button>
      <button v-on:click="autoTweet">自動ツイートテスト</button>
    </div>
  </div>


</template>

<script>
  import axios from "axios";
  import {OK} from "../util";
  import flatPickr from 'vue-flatpickr-component';
  import 'flatpickr/dist/flatpickr.css';

  export default {
    name: "Account",
    props: {
      item: {
        type: Object,
        required: true
      }
    },
    components: {
      flatPickr
    },
    data() {
      return {
        targets: [],  // ターゲットアカウント
        searchKeywords: [],  // 検索用キーワード
        favoriteKeywords:[],  // いいね用キーワード
        emptySearchKeyword:false,  // 検索用キーワードが空かどうか判定（画面描画用条件）
        emptyFavoriteKeyword:false,  // いいね用キーワードが空かどうか判定（画面描画用条件）
        reserve: {  // 予約ツイート用プロパティ
          tweet : '',  // ツイート内容
          reserved_at : '' // ツイート時間
        },
        config: { // 日時入力コンポーネント用プロパティ
          enableTime: true,
          dateFormat: "Y-m-d H:i",
          time_24hr: true,
          minDate: "today",
          defaultDate: "today"
        },
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
      addSearchKeywordForm() {
        const additionalForm = {
          selected: 'AND', // セレクトボックスの結果が入る(規定値：AND)
          text:'',  // 検索キーワード
          message: '',  // エラーメッセージ
          options: [  // セレクトボックスの選択肢
            'AND',
            'OR',
            'NOT'
          ]
        }
        this.searchKeywords.push(additionalForm)
      },
      deleteSearchKeywordForm(index){
        // クリックした削除ボタンに対応するフォームを削除
        this.searchKeywords.splice(index, 1);
      },
      async saveSearchKeywordForm(){
        for(let data of this.searchKeywords){
          this.emptySearchKeyword = false
          if(data.text !== ''){
            /*
            認証済みアカウントごとにサーチキーワードリストを作成するため
            twitter_usersテーブル内のidをプロパティとして持たせる
            */
            this.$set(data, 'twitter_user_id', this.item.id)

            // フォームに入力がある場合はエラーをクリア
            this.$set(data, 'message', '')

          }else {
            // フォームが空欄の場合はエラーを表示する
            this.$set(data, 'message', 'キーワードが存在しません')
            return false
          }
        }

        // 検索キーワードが一つも設定されていない場合、
        if(this.searchKeywords.length === 0){

          this.emptySearchKeyword = true
          this.searchKeywords.push({
            twitter_user_id: this.item.id,
            is_empty: true
          })
        }

        // フォームの入力チェック完了後、サーチキーワードリストの作成を行う
        const response = await axios.post('/api/search/keyword', this.searchKeywords)

        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }

        // if('is_empty' in this.searchKeywords[0]){
        //   this.searchKeywords.splice(-this.searchKeywords.length)
        // }

        this.$store.commit('message/setText', 'キーワードが保存されました', { root: true })
      },
      async querySearchForm(){
        // セレクトボックス表示用定数
        const options = ['AND', 'OR', 'NOT']

        // ターゲットアカウントリストの内容を呼び出す
        const response = await axios.get(`/api/search/keyword/${this.item.id}`);

        // サーチキーワードリストが存在する場合、フォームに展開する
        if(response.data.length !== 0){
          for (let data of response.data){
            data.options = options  // optionsプロパティ追加
            this.searchKeywords.push(data)
          }
        }
      },
      addFavoriteKeywordForm() {
        const additionalForm = {
          selected: 'AND', // セレクトボックスの結果が入る(規定値：AND)
          text:'',  // 検索キーワード
          message: '',  // エラーメッセージ
          options: [  // セレクトボックスの選択肢
            'AND',
            'OR',
            'NOT'
          ]
        }
        this.favoriteKeywords.push(additionalForm)
      },
      deleteFavoriteKeywordForm(index){
        // クリックした削除ボタンに対応するフォームを削除
        this.favoriteKeywords.splice(index, 1);
      },
      async saveFavoriteKeywordForm(){
        for(let data of this.favoriteKeywords){
          this.emptyFavoriteKeyword = false
          if(data.text !== ''){
            /*
            認証済みアカウントごとにサーチキーワードリストを作成するため
            twitter_usersテーブル内のidをプロパティとして持たせる
            */
            this.$set(data, 'twitter_user_id', this.item.id)

            // フォームに入力がある場合はエラーをクリア
            this.$set(data, 'message', '')

          }else {
            // フォームが空欄の場合はエラーを表示する
            this.$set(data, 'message', 'キーワードが存在しません')
            return false
          }
        }

        // 検索キーワードが一つも設定されていない場合、
        if(this.favoriteKeywords.length === 0){

          this.emptyFavoriteKeyword = true
          this.favoriteKeywords.push({
            twitter_user_id: this.item.id,
            is_empty: true
          })
        }

        // フォームの入力チェック完了後、サーチキーワードリストの作成を行う
        const response = await axios.post('/api/favorite/keyword', this.favoriteKeywords)

        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }

        // if('is_empty' in this.favoriteKeywords[0]){
        //   this.favoriteKeywords.splice(-this.favoriteKeywords.length)
        // }

        this.$store.commit('message/setText', 'キーワードが保存されました', { root: true })
      },
      async queryFavoriteForm(){
        // セレクトボックス表示用定数
        const options = ['AND', 'OR', 'NOT']

        // ターゲットアカウントリストの内容を呼び出す
        const response = await axios.get(`/api/favorite/keyword/${this.item.id}`);

        // サーチキーワードリストが存在する場合、フォームに展開する
        if(response.data.length !== 0){
          for (let data of response.data){
            data.options = options  // optionsプロパティ追加
            this.favoriteKeywords.push(data)
          }
        }
      },
      async queryReserve(){
        // 予約ツイートの内容を呼び出す
        const response = await axios.get(`/api/twitter/reserve/${this.item.id}`);

        //　未投稿の予約ツイートが存在する場合、フォームに展開する
        if(response.data.length !== 0){
          this.$set(this.reserve, 'tweet', response.data[0].tweet)
          this.$set(this.reserve, 'reserved_at', response.data[0].reserved_at)
        } else {
          this.$set(this.reserve, 'reserved_at', new Date)
        }
      },
      async autoFollow(){
        // 自動フォローを開始する(非同期)
        const responsePromise = axios.post(`/api/twitter/follow/${this.item.id}`);

        // 自動フォロー開始のスプラッシュメッセージを出す

        // 自動フォローが完了するのを待つ

        // 完了メール送信
      },
      async autoUnfollow(){
        // 自動アンフォローを開始する
        const responsePromise = axios.post(`/api/twitter/unfollow/${this.item.id}`);
      },
      async autoFavorite(){
        // 自動いいねを開始する
        const responsePromise = axios.post(`/api/twitter/favorite/${this.item.id}`);
      },
      async reserveTweet(){
        this.reserve.twitter_user_id = this.item.id;

        const response = await axios.post(`/api/twitter/reserve`, this.reserve);

        // TODO レスポンス結果でメッセージ表示
        console.log(response);
      },
      async autoTweet(){
        const responseTweet = axios.post('/api/twitter/tweet');
      },
      async deleteUser(){
        const response = await axios.post(`/api/twitter/user/delete/${this.item.id}`)

        if(response.status !== OK){
          this.$store.commit('error/setCode', response.status)
          this.$store.commit('error/setMessage', response.data.errors)
          return false
        }

        this.$store.commit('message/setText', '認証が解除されました', { root: true })

        location.reload();
      },
      async sendMail(){
        const response = await axios.post(`/api/send/mail/${this.item.id}`)
      }

    },
    created() {
      // ページ表示時にターゲットアカウントリストの内容を呼び出す
      this.queryTargetForm()
      // ページ表示時にサーチキーワードリストの内容を呼び出す
      this.querySearchForm()
      // ページ表示時にいいねキーワードリストの内容を呼び出す
      this.queryFavoriteForm()
      // ページ表示時に未投稿の予約ツイートを呼び出す
      this.queryReserve()
    }
  }
</script>

<style scoped>

</style>