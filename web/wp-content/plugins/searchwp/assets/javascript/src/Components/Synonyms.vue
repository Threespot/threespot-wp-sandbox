<template>
	<MetaBox
		class="searchwp-synonyms"
		:active="true"
		:label="'Synonyms'">
		<template v-slot:heading>
			<span>{{ 'Synonyms' | i18n }}</span>
			<Menu :items="menuItems">
				<button class="button">{{ 'Actions' | i18n }}</button>
			</Menu>
			<button
				class="button"
				@click.stop="add()">
				{{ 'Add New' | i18n }}
			</button>
			<button
				:disabled="!updated"
				class="button button-primary"
				@click.stop="save()">
				{{ 'Save' | i18n }}
			</button>
		</template>
		<template v-slot:content>
			<div class="inside">
				<component :is="i18nSynonymsNote"></component>
				<p class="description" v-if="!synonyms.length">{{ '_no_synonyms_note' | i18n }}</p>
				<table v-else>
					<thead>
						<tr>
							<th><Tooltip :content="'_synonyms_term_tooltip' | i18n">{{ 'Search Term(s)' | i18n }}</Tooltip></th>
							<th><Tooltip :content="'_synonyms_synonyms_tooltip' | i18n">{{ 'Synonym(s)' | i18n }}</Tooltip></th>
							<th><Tooltip :content="'_synonyms_replace_tooltip' | i18n">{{ 'Replace' | i18n }}</Tooltip></th>
						</tr>
					</thead>

					<draggable :tag="'tbody'" v-model="synonyms">
						<tr v-for="(synonym, index) in synonyms" :key="index + ( synonym.replace ? 'y' : 'n' )">
							<td>
								<div class="searchwp-synonyms-synonym-wrapper searchwp-actions--mini">
									<button class="button searchwp-button-subtle" @click="remove(index)">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
									<input type="text" @keyup.enter="add" v-model="synonym.sources">
								</div>
							</td>
							<td>
								<input type="text" @keyup.enter="add" v-model="synonym.synonyms">
							</td>
							<td>
								<Checkbox
									:id="'searchwp-synonym-' + index"
									:value="1"
									:checked="!!synonym.replace"
									@change="function(value) { synonym.replace = value; }"
								>{{ 'Replace' | i18n }}</Checkbox>
							</td>
						</tr>
					</draggable>
				</table>
			</div>
		</template>
	</MetaBox>
</template>

<script>
import Menu from './Menu.vue';
import MetaBox from './MetaBox.vue';
import Tooltip from './Tooltip.vue';
import isEqual from 'lodash.isequal';
import draggable from 'vuedraggable';
import { __ } from './../helpers.js';
import cloneDeep from 'lodash.clonedeep';
import Checkbox from './Inputs/Checkbox.vue';

export default {
	name: 'Synonyms',
	components: {
		draggable,
		Checkbox,
		MetaBox,
		Tooltip,
		Menu
	},
	computed: {
		updated: function() {
			return !isEqual(this.original, this.synonyms);
		},
		i18nSynonymsNote: function() {
			return {
				template: '<p>' + __('_synonyms_note') + '</p>'
			};
		},
		menuItems: function() {
			let vm = this;

			let items = [ {
					text: __('Sort ASC'),
					click: function() { vm.sort('ASC') }
				}, {
					text: __('Sort DESC'),
					click: function() { vm.sort('DESC') }
				}, {
					text: __('Remove All'),
					click: function() { vm.clear() }
				} ];

			return items;
		}
	},
	methods: {
		remove: function(index) {
			this.synonyms.splice(index, 1);
		},
		add: function() {
			this.synonyms.push({
				sources: '',
				synonyms: '',
				replace: false
			});
		},
		save: function() {
			let vm = this;

			jQuery.post(ajaxurl, {
				_ajax_nonce: _SEARCHWP.nonce,
				action: _SEARCHWP.prefix + 'synonyms_update',
				synonyms: JSON.stringify(vm.synonyms)
			}, function(response) {
				vm.synonyms = cloneDeep(response.data);
				vm.original = cloneDeep(response.data);
			});
		},
		checkForUnsaved: function(event) {
			if (this.updated) {
				event.preventDefault();
				// Chrome requires returnValue to be set.
				event.returnValue = '';
			}
		},
		sort: function(direction) {
			this.synonyms = this.synonyms.sort(function(a, b) {
				if (a.sources.toLowerCase() > b.sources.toLowerCase()) {
					return 'asc' === direction.toLowerCase() ? 1 : -1;
				} else if (b.sources.toLowerCase() > a.sources.toLowerCase()) {
					return 'asc' === direction.toLowerCase() ? -1 : 1;
				} else {
					return 0;
				}
			});
		},
		clear: function() {
			this.synonyms = [];
		}
	},
	beforeMount() {
		 window.addEventListener('beforeunload', this.checkForUnsaved);
	},
	beforeDestroy() {
		window.removeEventListener('beforeunload', this.checkForUnsaved);
	},
	data() {
		return {
			original: cloneDeep(_SEARCHWP.synonyms),
			synonyms: cloneDeep(_SEARCHWP.synonyms),
		}
	}
}
</script>

<style lang="scss">
	.searchwp-settings-view .searchwp-synonyms {

		table {

			td {
				padding-top: 0;
			}

			td,
			th {
				border: 0;
			}

			input[type="text"] {
				display: block;
				width: 94%;
			}
		}
	}

	.searchwp-synonyms-synonym-wrapper {
		display: flex;
		align-items: center;
		margin-right: calc(1em - 4px);
		margin-left: -4px; // Visual offset.
	}
</style>
