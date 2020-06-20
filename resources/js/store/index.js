import Vue from 'vue'
import Vuex from 'vuex'
import createPersistedState from 'vuex-persistedstate'

import auth from './auth'
import error from './error'
import message from './message'
import twitter from './twitter'

Vue.use(Vuex)

const store = new Vuex.Store({
  modules: {
    auth,
    error,
    message,
    twitter
  },
  plugins: [createPersistedState()]
})

export default store
