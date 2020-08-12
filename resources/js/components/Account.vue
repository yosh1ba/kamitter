<template>
  <div class="p-account">
    <div class="p-account__content">
      <div class="l-flex p-account__info">
        <img :src="item.twitter_avatar" alt="" class="p-account__info__img">
        <p class="p-account__info__name">{{item.twitter_screen_name}}</p>
        <button v-on:click="deleteUser" class="c-button__circle p-account__info__btn--delete"><i class="fas fa-trash"></i></button>
      </div>
      <div class="p-account__buttons">
        <p class="p-account__buttons__head">自動フォロー</p>
        <input type="checkbox" id="checkbox" v-model="checkUnfollow" class="p-account__buttons__check" :disabled="disableLink">
        <label for="checkbox" class="p-account__buttons__check__label">アンフォローを一緒に行う</label>
        <div class="l-flex">
          <div class="p-account__buttons__btn">
            <button v-on:click="autoFollow" class="c-button__circle p-account__buttons__btn--play" :disabled="disableAutoFollow"><i class="fas fa-play"></i></button>
            <p class="p-account__buttons__label">開始</p>
          </div>
          <div class="p-account__buttons__btn">
            <div v-if="!isPaused">
              <button v-on:click="toPause" class="c-button__circle p-account__buttons__btn--pause" :disabled="disablePause"><i class="fas fa-pause"></i></button>
              <p class="p-account__buttons__label">一時停止</p>
            </div>
            <div v-else-if="isPaused">
              <button v-on:click="toRestart" class="c-button__circle p-account__buttons__btn--pause" :disabled="disablePause"><i class="fas fa-reply-all"></i></button>
              <p class="p-account__buttons__label">再開</p>
            </div>
          </div>
          <div class="p-account__buttons__btn">
            <button v-on:click="toCancel" class="c-button__circle p-account__buttons__btn--cancel" :disabled="disableFollowCancel"><i class="fas fa-stop"></i></button>
            <p class="p-account__buttons__label">中止</p>
          </div>
        </div>
      </div>
      <div class="p-account__buttons">
        <p class="p-account__buttons__head">自動いいね</p>
        <div class="l-flex">
          <div class="p-account__buttons__btn">
            <button v-on:click="autoFavorite" class="c-button__circle p-account__buttons__btn--play" :disabled="disableAutoFavorite"><i class="fas fa-play"></i></button>
            <p class="p-account__buttons__label">開始</p>
          </div>
          <div class="p-account__buttons__btn">
            <button v-on:click="toCancelFavorite" class="c-button__circle p-account__buttons__btn--cancel" :disabled="disableFavoriteCancel"><i class="fas fa-stop"></i></button>
            <p class="p-account__buttons__label">中止</p>
          </div>
        </div>
      </div>
<!--      <div class="p-account__buttons&#45;&#45;sp">
        <button v-on:click="autoFollow" v-if="!disableAutoFollow" class="c-button__circle p-account__buttons__btn&#45;&#45;play"><i class="fas fa-play"></i></button>
        <button v-on:click="toCancel" v-else class="c-button__circle p-account__buttons__btn&#45;&#45;cancel"><i class="fas fa-stop"></i></button>
        <transition name="fade">
          <button v-on:click="toPause" v-if="disableAutoFollow && !disablePause" class="c-button__circle p-account__buttons__btn&#45;&#45;pause"><i class="fas fa-pause"></i></button>
          <button v-on:click="toRestart" v-else-if="disableAutoFollow && disablePause" class="c-button__circle p-account__buttons__btn&#45;&#45;restart"><i class="fas fa-reply-all"></i></button>
        </transition>
        <button v-on:click="deleteUser" class="c-button__circle p-account__buttons__btn&#45;&#45;delete"><i class="fas fa-trash"></i></button>
      </div>-->
      <!--<button v-on:click="autoUnfollow">自動アンフォロー</button>-->
      <button v-on:click="autoFavoriteTest">自動いいね</button>
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
      flatPickr,
    },
    data() {
      return {
        targets: [],  // ターゲットアカウント
        searchKeywords: [],  // 検索用キーワード
        favoriteKeywords:[],  // いいね用キーワード
        emptySearchKeyword:false,  // 検索用キーワードが空かどうか判定（画面描画用条件）
        emptyFavoriteKeyword:false,  // いいね用キーワードが空かどうか判定（画面描画用条件）
        disableAutoFollow:false,  // 自動フォローボタン不活性判定
        disablePause:true,  // 一時停止ボタン不活性判定
        isPaused:false, // 一時停止状態判定
        disableFollowCancel:true, // 自動フォロー中止ボタン不活性判定
        checkUnfollow:true, // アンフォロー判定
        disableAutoFavorite:false,  // 自動いいねボタン不活性判定
        disableFavoriteCancel:true, // 自動いいね中止ボタン不活性判定
        reserve: {  // 予約ツイート用プロパティ
          tweet : '',  // ツイート内容
          reserved_at : '' // ツイート時間
        },
        config: { // 日時入力コンポーネント用プロパティ
          enableTime: true, // 未来日時のみ指定可能
          dateFormat: "Y-m-d H:i",  // 日付フォーマット形式
          time_24hr: true,  // 24時間形式で表示
          minDate: "today", // 最小日時
          defaultDate: "today"  // 既定値
        },
      }
    },
    methods: {
      // フォロー対象フォームの追加用メソッド
      addTargetForm() {
        const additionalForm = {
          screen_name: '',
          message:''
        }
        this.targets.push(additionalForm)
      },

      // フォロー対象フォームの削除用メソッド
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
          alert('フォロー対象アカウントは入力必須です')
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

        return response
        // this.$store.commit('message/setText', 'ターゲットアカウントが保存されました', { root: true })
      },

      // DBへに保存したフォロー対象アカウントをフォームに展開するメソッド
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

      // フォロワーサーチキーワードフォーム追加用メソッド
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

      // フォロワーサーチキーワードフォーム削除用メソッド
      deleteSearchKeywordForm(index){
        // クリックした削除ボタンに対応するフォームを削除
        this.searchKeywords.splice(index, 1);
      },

      // フォロワーサーチキーワードフォーム保存用メソッド
      async saveSearchKeywordForm(){
        // if(this.searchKeywords.length === 0){
        //   alert('入力必須です')
        //   return false;
        // }
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
        return response;
        // this.$store.commit('message/setText', 'キーワードが保存されました', { root: true })
      },

      // DBに格納されているフォロワーサーチキーワードを展開するメソッド
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

      // いいね用キーワードフォーム追加用メソッド
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

      // いいね用キーワードフォーム削除用メソッド
      deleteFavoriteKeywordForm(index){
        // クリックした削除ボタンに対応するフォームを削除
        this.favoriteKeywords.splice(index, 1);
      },

      // いいね用キーワードフォーム保存用メソッド
      async saveFavoriteKeywordForm(){
        if(this.favoriteKeywords.length === 0){
          alert('いいねキーワードを入力して下さい')
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

        return response
      },

      // いいね用キーワードフォーム展開用メソッド
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

      // 予約ツイート展開用メソッド
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

      // 予約ツイート保存用メソッド
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

        this.$store.commit('message/setText', 'ツイートを予約しました', { root: true })
      },

      // 自動フォロー状態確認用メソッド
      async queryStateAutoFollow(){
        const response = await axios.get(`/api/twitter/auto/${this.item.id}`);

        /*
        * 自動フォロー処理中の場合は、
        *   自動フォローボタン 不活性
        *   一時停止ボタン     活性
        *   中止ボタン         活性
        * という状態にする
        */
        if(response.data.length !== 0) {
          this.disableAutoFollow = true
          this.disablePause = false
          this.disableFollowCancel = false
        }
      },

      // 一時停止状態確認用メソッド
      async queryStatePause(){
        const response = await axios.get(`/api/twitter/pause/${this.item.id}`);

        /*
        * 一時停止中の場合は、
        *   isPaused = true
        * とし、再開ボタンに表示を切り替える
        */
        if(response.data.length !== 0) {
          this.isPaused = true
        }
      },

      // 自動フォロー状態確認用メソッド
      async queryStateAutoFavorite(){
        const response = await axios.get(`/api/twitter/auto/favorite/${this.item.id}`);

        /*
        * 自動いいね処理中の場合は、
        *   自動いいねボタン 不活性
        *   中止ボタン         活性
        * という状態にする
        */
        if(response.data.length !== 0) {
          this.disableAutoFavorite = true
          this.disableFavoriteCancel = false
        }
      },

      // 自動フォロー（自動運用）開始用メソッド
      async autoFollow(){

        // フォロー対象アカウント保存
        const responseTargets = await this.saveTargetForm()
        if(!responseTargets){
          return false
        }

        // 検索キーワード保存
        const responseKeywords = await this.saveSearchKeywordForm()
        if(!responseKeywords){
          return false
        }

        // 自動アンフォロー状態更新
        const responseUnfollow = await axios.post(`/api/twitter/unfollow/update`, {
          id: this.item.id,
          unfollow: this.checkUnfollow
        });

        // 自動フォローを開始する
        this.disableAutoFollow = true
        this.disablePause = false
        this.disableFollowCancel = false

        this.$store.commit('message/setText', '自動フォローを開始しました', { root: true })

        const responsePromise = await axios.post(`/api/twitter/follow/${this.item.id}`);

        this.disableAutoFollow = false
        this.disablePause = true
        this.disableFollowCancel = true
      },

      // 自動フォロー 一時停止用メソッド
      async toPause(){
        // 自動処理を一時停止する
        this.isPaused = true
        const responsePromise = axios.post(`/api/twitter/pause/${this.item.id}`);
      },

      // 自動フォロー 中止用メソッド
      async toCancel(){
        // 自動処理を中止する
        this.disableAutoFollow = false
        this.disablePause = true
        this.isPaused = false
        this.disableFollowCancel = true
        const responsePromise = axios.post(`/api/twitter/cancel/${this.item.id}`);
      },

      // 自動フォロー 再開用メソッド
      async toRestart(){
        // 自動処理を再開する
        this.isPaused = false
        const responsePromise = axios.post(`/api/twitter/restart/${this.item.id}`);
      },

      // 自動いいね開始用メソッド
      async autoFavorite(){

        // いいねキーワード保存
        const responseKeywords = await this.saveFavoriteKeywordForm()
        if(!responseKeywords){
          return false
        }

        // 自動いいねを開始する
        this.disableAutoFavorite = true
        this.disableFavoriteCancel = false
        const responsePromise = await axios.post(`/api/twitter/favorite/update`, {
          id: this.item.id,
          favorite: true
        });

        this.$store.commit('message/setText', '自動いいねを開始しました', { root: true })

      },

      // 自動いいね 中止用メソッド
      async toCancelFavorite(){
        // 自動いいねを中止する
        this.disableAutoFavorite = false
        this.disableFavoriteCancel = true
        const responsePromise = await axios.post(`/api/twitter/favorite/update`, {
          id: this.item.id,
          favorite: false
        });

        this.$store.commit('message/setText', '自動いいねを中止しました', { root: true })
      },

      // 認証用ユーザー解除用メソッド
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

      // 自動アンフォロー開始用メソッド（テスト用）
      async autoUnfollow(){
        // 自動アンフォローを開始する
        const responsePromise = axios.post(`/api/twitter/unfollow/${this.item.id}`);
      },

      // 自動ツイートメソッド（テスト用）
      async autoTweet(){
        const responseTweet = axios.post('/api/twitter/tweet');
      },

      // メール送信用メソッド（テスト用）
      async sendMail(){
        const response = await axios.post(`/api/send/mail/${this.item.id}`)
      },

      async autoFavoriteTest(){
       const response = await axios.post('/api/favorite/test')
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
      // 自動運転の状態を取得する
      this.queryStateAutoFollow()
      // 一時停止の状態を確認する
      this.queryStatePause()
      // 自動いいねの状態を取得する
      this.queryStateAutoFavorite()

    },
    computed: {
      disableLink: function () {
        return this.disableAutoFollow
      }
    }
  }
</script>

<style scoped>

</style>