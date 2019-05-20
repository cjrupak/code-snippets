import moment from 'moment';
require("moment/min/locales.min");
import VueMoment from 'vue-moment';
import VueLocalStorage from 'vue-localstorage';
import VueClip from 'vue-clip';
import router from './routes';
import store from './store';
import {sync} from 'vuex-router-sync';
import App from './App.vue';
import helpers from '../../../../../../source/scripts/frontend/components/helpers.js';
import uiHelpers from '../../../../../../source/scripts/ui-helpers';
import frontendForms from '../../../../../../source/scripts/frontend/components/frontend-forms';

export function run() {
    sync(store, router);
    Vue.http.interceptors.push(function (request, next) {
        request.headers.set('X-Authorization-Token', Vue.cookie.get('cjaddons_token'));
        next();
    });

    Vue.http.options.emulateJSON = true;
    Vue.http.options.emulateHTTP = true;
    moment.locale(localize['addon-supportezzy'].lang);
    Vue.use(VueLocalStorage);
    Vue.use(VueMoment);
    Vue.use(VueClip);

    router.beforeEach(function (to, from, next) {
        store.commit('HIDE_SIDEBAR', 'sidebar_main_nav');
        store.commit('HIDE_SIDEBAR', 'sidebar_organization');
        store.commit('HIDE_SIDEBAR', 'sidebar_user');
        if (to.meta !== undefined && to.meta.require_auth === 1) {
            Vue.http.headers.common['X-Authorization'] = Vue.cookie.get('cjaddons_token');
            Vue.http.post(localize.api_url + 'addon-supportezzy/me').then(function (result) {
                store.commit('TOGGLE_LOADING', 0);
                if (result.body !== null && result.body.data.ID === undefined) {
                    next({name: 'login'});
                    store.commit('REDIRECT', to);
                    return;
                } else {
                    next();
                    return;
                }
            }, function (error) {
                if (error.status === 403) {
                    next({name: 'login'});
                    store.commit('REDIRECT', to);
                    return;
                }
            });

        } else {
            // 404 configuration
            if (to.name === '404') {
                next({name: 'home'});
                return;
            }
            next();
        }
    });

    router.afterEach(function (to, from, next) {
        /*let body = document.getElementsByTagName('body')[0];
         body.setAttribute('data-view', to.name);*/
        helpers();
        uiHelpers();
        frontendForms();
    });
    //store.watch()

    new Vue({
        el: '#supportezzy-app',
        router,
        store,
        template: '<App api_url="' + document.getElementById('supportezzy-app').getAttribute('data-api-url') + '"/>',
        components: {App},
    });
}