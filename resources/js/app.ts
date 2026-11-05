import { initTheme, useRequestKey } from 'ornito';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import App from '@/App.vue';
import { useRequest } from '@/core/composables/useRequest';
import router from '@/router';

initTheme();

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.provide(useRequestKey, useRequest);

app.mount('#app');
