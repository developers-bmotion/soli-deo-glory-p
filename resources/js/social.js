require('./bootstrap');
window.Vue = require('vue');

import { WebRTC } from 'vue-webrtc'
Vue.component(WebRTC.name, WebRTC)
    /*=============================================
    COMPONENTES PARA LA VIDEO CONFERENCIA
    =============================================*/
Vue.component('social-video-conference', require('./social/video-conference/VideoConference.vue').default);
window.eventBus = new Vue()
const app = new Vue({
    el: '#social',
});