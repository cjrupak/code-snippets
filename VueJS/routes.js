import Router from 'vue-router'
import Home from './components/Home.vue'
import ticketsList from './components/tickets/list.vue'
import ticketsSingle from './components/tickets/single.vue'
import ticketsAddNew from './components/tickets/add-new.vue'
import faqsList from './components/faqs/list.vue'
import Login from './components/global/Login.vue'

Vue.use(Router)
let app_path = '/' +
  document.getElementById('supportezzy-app').getAttribute('data-base-path')
export default new Router({
  mode: 'hash', // hash, history, abstract
  //base: '/' + app_path,
  linkActiveClass: 'cj-is-active',
  routes: [
    {
      path: '/',
      name: 'home',
      component: Home,
    },
    {
      path: '/login',
      name: 'login',
      component: Login,
      meta: {top_nav: 0},
    },
    {
      path: '/tickets',
      name: 'tickets',
      component: ticketsList,
      meta: {require_auth: 1},
    },
    {
      path: '/tickets/add-new',
      name: 'tickets-add-new',
      component: ticketsAddNew,
    },
    {
      path: '/tickets/:id',
      name: 'ticket',
      component: ticketsSingle,
      meta: {require_auth: 1},
    },
    {
      path: '/faqs',
      name: 'faqs',
      component: faqsList,
    },
    {
      name: '404',
      path: '*',
    },
  ],
})