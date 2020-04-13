require('./bootstrap');
window.Vue = require('vue');

/*=============================================
COMPONENTES PARA LA VIDEO CONFERENCIA
=============================================*/
Vue.component('social-video-conference', require('./social/video-conference/VideoConference.vue').default);
window.eventBus = new Vue()
const app = new Vue({
    el: '#social',
});