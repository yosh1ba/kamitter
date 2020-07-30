import axios from 'axios';
import {CREATED, OK, UNPROCESSABLE_ENTITY} from '../util'

const state = {

  user: null, // twitter API用

  apiStatus: null,  // APIステータス管理
}

const getters = {
  id: state => state.user ? state.user.id : '',
  oauthToken: state => state.user ? state.user.id : '',
  isAutoPilot: state => state.user ? state.user: ''
}

const mutations = {
  setUser(state, user){
    state.user = user
  },

  setApiStatus(state, status) {
    state.apiStatus = status
  },
}

const actions = {
  async auth(context, path){
    context.commit('setApiStatus', null)
    context.commit('message/setText', null, { root: true })

    const response = await axios.get('/twitter/register', { params: path })

    // ログイン成功の場合
    if (response.status === OK) {
      context.commit('setApiStatus', true)
      context.commit('message/setText', 'twitter認証が完了しました', { root: true })
      return false
    }

    context.commit('setApiStatus', false)
    if (response.status === UNPROCESSABLE_ENTITY) {
      // context.commit('setLoginErrorMessages', response.data.errors)
    } else {
      context.commit('error/setCode', response.status, { root: true })
    }
  },
  async authenticatedUser(context, data){
    const response = await axios.get(`/api/twitter/user/${data}`)

    // 認証済みtwitterユーザー取得成功の場合
    if (response.status === OK) {
      // 認証済みtwitterユーザーの一覧をVuexへ保存
      context.commit('setUser', response.data)
      return false
    }

  }

}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}