const state = {
  text: null  // 表示するメッセージ
}

const mutations = {
  setText(state, message) {
    state.text = message
  }
}

export default {
  namespaced: true,
  state,
  mutations
}