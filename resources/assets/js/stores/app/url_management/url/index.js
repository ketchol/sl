/**
 * Created by Ivan on 4/03/2017.
 */

import Vue from 'vue';
import Vuex from 'vuex';
Vue.use(Vuex);

import defaultStore from '../../../default';

export default new Vuex.Store({
    modules: {
        defaultStore,
        index: {
            state: {},
            mutations: {},
            actions: {},
            getters: {}
        }
    }
});