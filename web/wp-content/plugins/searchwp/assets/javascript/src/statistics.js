import Vue from 'vue';
import VueTabs from 'vue-nav-tabs/dist/vue-tabs.js';
import VModal from 'vue-js-modal';
import vSelect from 'vue-select';

import Statistics from './Components/Statistics.vue';

import { __ } from './helpers.js';

Vue.use(VueTabs);
Vue.use(VModal, {componentName: 'v-modal'});

vSelect.props.components.default = () => ({
	Deselect: {
		render: createElement => createElement('span', { class: 'dashicons dashicons-no-alt' } ),
	},
	OpenIndicator: {
		render: createElement => createElement('span', { class: 'dashicons dashicons-arrow-down-alt2' } ),
	},
});

Vue.component('v-select', vSelect);

Vue.filter('i18n', function (source, placeholders = []) {
	return __( source, placeholders );
})

new Vue({
	el: '#searchwp-statistics',
	render: h => h(Statistics)
});