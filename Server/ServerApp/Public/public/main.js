import App from '../components/App.js';
import router from './router.js';

const app = new Vue({
    router,
    render: h => h(App),
}).$mount('#app');