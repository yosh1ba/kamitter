import axios from 'axios';
import {CREATED, OK, UNPROCESSABLE_ENTITY} from '../util'

const state = {
  user: null,
  apiStatus: null,
  loginErrorMessages: null,
  registerErrorMessages: null
}

const getters = {
  // ログイン状態の真偽値を返すゲッター
  check: state => !! state.user,
  
  verified: state => state.user ? state.user.email_verified_at : '',

  // ユーザーIDを返すゲッター
  userid: state => state.user ? state.user.id : '',
  
  // ユーザー名を返すゲッター
  username: state => state.user ? state.user.name : '',
  
  // メールアドレスを返すゲッター
  email: state => state.user ? state.user.email : ''
}

const mutations = {
  setUser(state, user){
    state.user = user
  },

  setApiStatus(state, status) {
    state.apiStatus = status
  },

  setLoginErrorMessages (state, messages) {
    state.loginErrorMessages = messages
  },

  setRegisterErrorMessages (state, messages) {
    state.registerErrorMessages = messages
  }
}

const actions = {
  // ユーザー登録
  async register(context, data){
    context.commit('setApiStatus', null)
    const response = await axios.post('/api/register', data)
    // エラーレスポンスが帰ってきた場合の処理を resources/js/bootstrap.js に記述している

    if (response.status === CREATED) {
      context.commit('setApiStatus', true)
      context.commit('setUser', response.data)
      context.commit('message/setText', 'ユーザ登録が完了しました。メールアドレスの認証を行って下さい。', { root: true })
      return false
    }

    context.commit('setApiStatus', false)
    if (response.status === UNPROCESSABLE_ENTITY) {
      context.commit('setRegisterErrorMessages', response.data.errors)
    } else {
      context.commit('error/setCode', response.status, { root: true })
    }
  },

  // ログイン
  async login(context, data){
    context.commit('setApiStatus', null)
    const response = await axios.post('/api/login', data)
    // エラーレスポンスが帰ってきた場合の処理を resources/js/bootstrap.js に記述している

    // ログイン成功の場合
    if (response.status === OK) {
      context.commit('setApiStatus', true)
      context.commit('setUser', response.data)
      context.commit('message/setText', 'ログインが完了しました', { root: true })
      return false
    }

    context.commit('setApiStatus', false)
    if (response.status === UNPROCESSABLE_ENTITY) {
      context.commit('setLoginErrorMessages', response.data.errors)
    } else {
      context.commit('error/setCode', response.status, { root: true })
    }
  },

  // ログアウト
  async logout (context) {
    context.commit('setApiStatus', null)
    const response = await axios.post('/api/logout')

    if (response.status === OK) {
      context.commit('setApiStatus', true)
      context.commit('setUser', null)
      context.commit('message/setText', 'ログアウトが完了しました', { root: true })
      return false
    }

    context.commit('setApiStatus', false)
    context.commit('error/setCode', response.status, { root: true })
  },

  // ログインユーザーチェック
  async currentUser (context) {
    context.commit('setApiStatus', null)
    const response = await axios.get('/api/user')
    const user = response.data || null

    if (response.status === OK) {
      context.commit('setApiStatus', true)
      context.commit('setUser', user)
      return false
    }

    context.commit('setApiStatus', false)
    context.commit('error/setCode', response.status, { root: true })
  },
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}