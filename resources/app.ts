import { initTheme, useRequestKey } from 'ornito';
import { createApp } from 'vue';
import './css/app.css';
import App from '@/App.vue';
import { useRequest } from '@/core/composables/useRequest';
import router from '@/router';

import 'ornito/style.css';

initTheme();

const app = createApp(App);

app.use(router);
app.provide(useRequestKey, useRequest);

app.mount('#app');
