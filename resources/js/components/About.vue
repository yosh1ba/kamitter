<template>
  <div class="l-wrapper__about">
    <div class="l-about">
      <div class="p-about">
        <h2 class="p-about__header">Twitterをもっと活用したい。<br>
          でも、時間はかけたくない・・・</h2>
        <p class="p-about__detail">
          4種類の自動機能であなたのTwitter運用をサポートします。<br>
          <span class="p-about__detail--small">※最新のAPI制限に対応済み（2020/8/18時点）</span>
        </p>
        <div class="l-flex p-about__card">
          <Card
            v-for="card in cards"
            :key="card.id"
            :card="card"
          />
        </div>
        <div class="p-about__button">
          <router-link class="p-about__button__link" to="/signup">登録はこちらから</router-link>
        </div>
      </div>
    </div>
  </div>

</template>

<script>
  import Card from "./Card";

  export default {
    name: "About",
    data() {
      return {
        cards: [
          {title: '自動フォロー',
          icon: 'fas fa-user-plus',
          description: '任意のアカウントのフォロワーに対して、自動フォローが可能です。キーワード指定による対象の絞り込みも行えます。'},
          {title: '自動アンフォロー',
          icon: 'fas fa-user-minus',
          description: '自動アンフォローが可能です。過剰フォローによる利用制限を気にする必要もありません。'},
          {title: '自動いいね',
          icon: 'fas fa-heart',
          description: '任意のキーワードを指定し、自動いいねが可能です。タイムラインをポチポチする必要はもうありません。'},
          {title: '自動ツイート',
          icon: 'fas fa-comment-alt',
          description: '指定日時にツイートの予約が可能です。標準では行えない予約の複数登録が行えます。'},
          {title: '複数アカウント管理',
          icon: 'fas fa-user-friends',
          description: '最大10個のTwitterアカントを紐付け可能です。'},
          {title: '完全無料',
          icon: 'fab fa-creative-commons-nc-jp',
          description: '当サービスの利用に費用は一切かかりません。'},
        ]
      }
    },
    components: {
      Card
    },
  }
</script>

<style scoped>

</style>