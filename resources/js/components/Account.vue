<template>
  <div class="p-account">
    <div class="p-account__content">
      <div class="l-flex p-account__info">
        <img :src="item.twitter_avatar" alt="" class="p-account__info__img">
        <p class="p-account__info__name">{{item.twitter_screen_name}}</p>
        <div class="p-account__info__buttons">
          <button v-on:click="autoFollow" v-if="!autoPilot" class="c-button__circle p-account__info__buttons__btn--play"><i class="fas fa-play"></i></button>
          <button v-on:click="toCancel" v-else class="c-button__circle p-account__info__buttons__btn--cancel"><i class="fas fa-stop"></i></button>
          <transition name="fade">
            <button v-on:click="toPause" v-if="autoPilot && !pause" class="c-button__circle p-account__info__buttons__btn--pause"><i class="fas fa-pause"></i></button>
            <button v-on:click="toRestart" v-else-if="autoPilot && pause" class="c-button__circle p-account__info__buttons__btn--restart"><i class="fas fa-reply-all"></i></button>
          </transition>
          <button v-on:click="deleteUser" class="c-button__circle p-account__info__buttons__btn--delete"><i class="fas fa-trash"></i></button>
        </div>
      </div>
      <div class="p-account__info__buttons--sp">
        <button v-on:click="autoFollow" v-if="!autoPilot" class="c-button__circle p-account__info__buttons__btn--play"><i class="fas fa-play"></i></button>
        <button v-on:click="toCancel" v-else class="c-button__circle p-account__info__buttons__btn--cancel"><i class="fas fa-stop"></i></button>
        <transition name="fade">
          <button v-on:click="toPause" v-if="autoPilot && !pause" class="c-button__circle p-account__info__buttons__btn--pause"><i class="fas fa-pause"></i></button>
          <button v-on:click="toRestart" v-else-if="autoPilot && pause" class="c-button__circle p-account__info__buttons__btn--restart"><i class="fas fa-reply-all"></i></button>
        </transition>
        <button v-on:click="deleteUser" class="c-button__circle p-account__info__buttons__btn--delete"><i class="fas fa-trash"></i></button>
      </div>
      <!--<button v-on:click="autoUnfollow">自動アンフォロー</button>-->
      <!--<button v-on:click="autoFavorite">自動いいね</button>-->
      <!--<button v-on:click="sendMail">メール送信</button>-->
      <div class="p-account__content__forms">
        <p class="p-account__content__forms__title">フォロー対象アカウント</p>
        <div v-for="(target, index) in targets" class="p-account__form">
          <span class="p-account__form__msg">{{target.message}}</span>
          <input type="text" v-model="target.screen_name" placeholder="@以降の名前" class="p-account__form__input">
          <button v-on:click="deleteTargetForm(index)" class="c-button__square p-account__form__btn">削除</button>

        </div>
        <button v-on:click="addTargetForm" class="c-button__square">追加</button>
        <button v-on:click="saveTargetForm" class="c-button__square">保存</button>
      </div>
      <div class="p-account__content__forms">
        <p class="p-account__content__forms__title">検索キーワード</p>
        <div v-for="(searchKeyword, index) in searchKeywords" v-if="!emptySearchKeyword" class="p-account__form">
          <span class="p-account__form__msg">{{searchKeyword.message}}</span>
          <select v-model="searchKeyword.selected" class="p-account__form__select">
            <option v-for="option in searchKeyword.options">
              {{ option }}
            </option>
          </select>
          <input type="text" v-model="searchKeyword.text" class="p-account__form__keyword">
          <button v-on:click="deleteSearchKeywordForm(index)" class="c-button__square p-account__form__btn">削除</button>
        </div>
        <button v-on:click="addSearchKeywordForm" class="c-button__square">追加</button>
        <button v-on:click="saveSearchKeywordForm" class="c-button__square">保存</button>
      </div>
      <div class="p-account__content__forms">
        <p class="p-account__content__forms__title">いいねキーワード</p>
        <div v-for="(favoriteKeyword, index) in favoriteKeywords" v-if="!emptyFavoriteKeyword" class="p-account__form">
          <span class="p-account__form__msg">{{favoriteKeyword.message}}</span>
          <select v-model="favoriteKeyword.selected" class="p-account__form__select">
            <option v-for="option in favoriteKeyword.options">
              {{ option }}
            </option>
          </select>
          <input type="text" v-model="favoriteKeyword.text" class="p-account__form__keyword">
          <button v-on:click="deleteFavoriteKeywordForm(index)" class="c-button__square p-account__form__btn">削除</button>
        </div>
        <button v-on:click="addFavoriteKeywordForm" class="c-button__square">追加</button>
        <button v-on:click="saveFavoriteKeywordForm" class="c-button__square">保存</button>
      </div>

      <div class="p-account__content__forms">
        <p class="p-account__content__forms__title">ツイート予約</p>
        <flat-pickr v-model="reserve.reserved_at" :config="config"></flat-pickr>
        <div>
          <span class="p-account__form__msg">{{reserve.message}}</span>
          <textarea name="" id="" rows="7" maxlength="140" v-model="reserve.tweet" class="p-account__form__textarea"></textarea>
        </div>
        <button v-on:click="reserveTweet" class="c-button__square">予約</button>
        <!--<button v-on:click="autoTweet" class="c-button__square p-account__form__btn">自動ツイートテスト</button>-->
      </div>
      <hr class="c-hr">
    </div>
  </div>
</template>

<script>
  import axios from "axios";
  import {OK} from "../util";
  import flatPickr from 'vue-flatpickr-component';
  import 'flatpickr/dist/flatpickr.css';
  import {mapGetters, mapState} from "vuex";

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
        autoPilot:false,
        pause:false,
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
        if (this.targets.length === 1){
          alert('ターゲットを空にはできません')
        }else {
          this.targets.splice(index, 1);
        }
      },
      async saveTargetForm(){
        if(this.targets.length === 0){
          alert('入力必須です')
          return false;
        }
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
        if(this.searchKeywords.length === 0){
          alert('入力必須です')
          return false;
        }
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
        if(this.favoriteKeywords.length === 0){
          alert('入力必須です')
          return false;
        }
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
      async queryStateAutoPilot(){
        const response = await axios.get(`/api/twitter/auto/${this.item.id}`);

        if(response.data.length !== 0) {
          this.autoPilot = true
        }
      },
      async queryStatePause(){
        const response = await axios.get(`/api/twitter/pause/${this.item.id}`);

        if(response.data.length !== 0) {
          this.pause = true
        }
      },
      async autoFollow(){
        // 自動フォローを開始する(非同期)
        this.autoPilot = true
        const responsePromise = axios.post(`/api/twitter/follow/${this.item.id}`);
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
        if(this.reserve.tweet !== ''){
          // フォームに入力がある場合はエラーをクリア
          this.$set(this.reserve, 'message', '')
          this.reserve.twitter_user_id = this.item.id;
          const response = await axios.post(`/api/twitter/reserve`, this.reserve);
        }else{
          this.$set(this.reserve, 'message', 'ツイート内容が存在しません')
          return false
        }


      },
      async autoTweet(){
        const responseTweet = axios.post('/api/twitter/tweet');
      },
      async toPause(){
        // 自動処理を一時停止する
        this.pause = true
        const responsePromise = axios.post(`/api/twitter/pause/${this.item.id}`);
      },
      async toCancel(){
        // 自動処理を中止する
        this.autoPilot = false
        this.pause = false
        const responsePromise = axios.post(`/api/twitter/cancel/${this.item.id}`);
      },
      async toRestart(){
        // 自動処理を再開する
        this.pause = false
        const responsePromise = axios.post(`/api/twitter/restart/${this.item.id}`);
      },
      async deleteUser(){
        if(window.confirm('連携を解除してもよろしいですか') === false){
          return false;
        }
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
      this.queryStateAutoPilot()
      this.queryStatePause()
    }
  }
</script>

<style scoped>

</style>