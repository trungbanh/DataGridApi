import '../css/app.css'
import Datagrid from './components/DataGrid.vue';


Vue.component('datagrid', Datagrid);

/**
 * Filter.
 */
Vue.filter('truncate', function (value, limit, trail) {
    if (!value) value = '';

    limit = limit ? limit : 20;
    trail = trail ? trail : '...';

    return value.length > limit ? value.substring(0, limit) + trail : value;
});

/**
 * Get laravel CSRF token.
 */
Vue.prototype.getCsrf = () => {
    let token = document.head.querySelector('meta[name="csrf-token"]');

    if (!token) {
        console.error(
            'CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token'
        );
    }

    return token.content;
};


let app = new Vue({
    el: '#app'
})
