//need:app/lib/view/cwidget.js,app/lib/view/cgrid_state.js,app/lib/controller/cgrid.js
/*
#--------------------------------
# Copyright (c) 2011 "Capensis" [http://www.capensis.com]
#
# This file is part of Canopsis.
#
# Canopsis is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Canopsis is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with Canopsis.  If not, see <http://www.gnu.org/licenses/>.
# ---------------------------------
*/
Ext.define('widgets.list2.list2' , {
	extend: 'canopsis.lib.view.cwidget',

	alias: 'widget.list2',

	requires: [
		'canopsis.lib.view.cgrid_state2',
		'canopsis.lib.controller.cgrid'
	],

	//don't work
	filter: {'source_type': 'component'},

	//Default options
	pageSize: global.pageSize,
	show_component: true,
	show_resource: true,
	show_state: true,
	show_state_type: true,
	show_source_type: true,
	show_last_check: true,
	show_output: true,
	show_tags: false,
	show_file_help: false,
	show_file_equipement: false,
	show_ticket: false,
	show_ack: true,
	show_help_msg: true,
	
	show_form_ack: false,
	
	show_form_edit: false,
	show_edit_state: true,
	show_edit_state_type: false,
	show_edit_ticket: true,
	show_edit_output: true,

	show_consolesup: false,

	paging: true,
	bar_search: true,
	reload: true,
	bar: true,
	hideHeaders: false,
	scroll: true,
	column_sort: true,

	fitler_buttons: false,

	default_sort_column: 'state',
	default_sort_direction: 'DESC',
	//..

	// Variables para agregar columna de Wiki y Mantis
	show_wiki: true,
	show_mantis: true,
	clientes_estandar: '',

	afterContainerRender: function() {
		if(this.reload || this.bar_search) {
			this.bar = true;
		}
		else {
			this.bar = false;
		}

		this.grid = Ext.create('canopsis.lib.view.cgrid_state2', {
			exportMode: this.exportMode,
			opt_paging: this.paging,
			filter: this.filter,
			pageSize: this.pageSize,
			remoteSort: true,
			sorters: [{
				property: this.default_sort_column,
				direction: this.default_sort_direction
			}],
		
			opt_show_consolesup: this.show_consolesup,

			opt_show_component: this.show_component,
			opt_show_resource: this.show_resource,
			opt_show_state: this.show_state,
			opt_show_state_type: this.show_state_type,
			opt_show_source_type: this.show_source_type,
			opt_show_last_check: this.show_last_check,
			opt_show_output: this.show_output,
			opt_show_tags: this.show_tags,
			opt_show_ticket: this.show_ticket,
			opt_show_file_help: this.show_file_help,
			opt_show_file_equipement: this.show_file_equipement,
			opt_show_ack: this.show_ack,
			opt_show_help_msg: this.show_help_msg,

			opt_help_msg: this.help_msg,
			opt_file_help_url: this.file_help_url,
			opt_file_equipement_url: this.file_equipement_url,
			opt_ticket_url: this.ticket_url,

			opt_column_sortable: this.column_sort,

			opt_show_form_ack: this.show_form_ack,
			opt_show_ack_state_solved: this.show_ack_state_solved,
			opt_show_ack_state_pendingsolved: this.show_ack_state_pendingsolved,
			opt_show_ack_state_pendingaction: this.show_ack_state_pendingaction,
			opt_show_ack_state_pendingvalidation: this.show_ack_state_pendingvalidation,

			opt_show_form_edit: this.show_form_edit,
			opt_show_edit_ticket: this.show_edit_ticket,
			opt_show_edit_state: this.show_edit_state,
			opt_show_edit_state_type: this.show_edit_state_type,
			opt_show_edit_output: this.show_edit_output,

			opt_bar: this.bar,
			opt_bar_search: this.bar_search,
			opt_bar_search_field: ['_id'],

			opt_bar_add: false,
			opt_bar_duplicate: false,
			opt_bar_reload: this.reload,
			opt_bar_delete: false,
			hideHeaders: this.hideHeaders,
			scroll: this.scroll,

			fitler_buttons: this.fitler_buttons,

			// Agregar columna de Wiki y Mantis
			opt_show_wiki: this.show_wiki,
			opt_show_mantis: this.show_mantis,
			opt_clientes_estandar: this.clientes_estandar
		});

		// Bind buttons
		this.ctrl = Ext.create('canopsis.lib.controller.cgrid');
		this.on('afterrender', function() {
			this.ctrl._bindGridEvents(this.grid);
		}, this);
		
		var event_ack = {};

		this.grid.store.load();
		this.grid.store.filter(function(rec, id) {
			var ans;

			if(rec.raw['event_type'] === 'ack') {
				event_ack[rec.raw['ref_rk']] = rec;
				ans = false;
			}
			else {
				if(typeof(event_ack[rec.raw['rk']]) !== 'undefined') {
					rec.raw['ack_state'] = event_ack[rec.raw['rk']].raw['state'];
					rec.raw['ack_output'] = event_ack[rec.raw['rk']].raw['output'];
				}

				ans = true;
			}

			/* exclude events that are in downtime period */
			if(rec.raw['state'] !== 0 && rec.raw['downtime']) {
				ans = false;
			}

			return ans;
		}, this);

		this.wcontainer.removeAll();
		this.wcontainer.add(this.grid);

		this.ready();

		this.grid.store.on('load', function() {

			var $loading = $('<img src="themes/canopsis/resources/images/loader.gif" alt="loading" class="loading">').css({
				'position': "absolute",
				'top': "50%",
				'left': "50%",
				'margin-top': "-8px",
				'margin-left': "-8px"
			});

			var url = "http://190.111.249.180/integracion_canopsis/";
				
			$('.c_wiki').each(function() {
				$(this).unbind('click.wiki').bind("click.wiki", function(e) {
					e.preventDefault();

					var cliente = $(this).data('cliente');
					var host = $(this).data('host');
					var servicio = $(this).data('servicio');
					var estado = $(this).data('estado');
					if (estado === 1) {
						estado = "warning";
					} else if (estado === 2) {
						estado = "critical";
					}

					var $dialog = $('<div></div>').append($loading.clone());

					$dialog.load(url, {cliente: cliente, host: host, servicio: servicio, estado: estado, integracion: 'wiki'}).dialog(
					{
						title: "Help",
						width: 350,
						height: 250,
						modal: true,
						position: 'center',
						buttons: {
							"CERRAR": function() {
								$(this).dialog('destroy').remove();
							}															
						},							
						close: function(event, ui) {
							$(this).dialog('destroy').remove();
						}
					});											
					
					$(this).click(function() {
						$dialog.dialog('open');
						return false;						
					});

				}); // Fin $(this).unbind()
			}); // Fin $('.wiki').each()

			$('.c_mantis').each(function() {
				$(this).unbind('click.mantis').bind("click.mantis", function(e) {
					e.preventDefault();

					var cliente = $(this).data('cliente');
					var host = $(this).data('host');
					var servicio = $(this).data('servicio');
					var estado = $(this).data('estado');
					if (estado === 1) {
						estado = "warning";
					} else if (estado === 2) {
						estado = "critical";
					}

					var $dialog = $('<div></div>').append($loading.clone());

					$dialog.html("<b>Crear ticket?</b>").dialog(
					{
						title: "Apertura de Ticket en Mantis",
						width: 550,
						height: 350,
						modal: true,
						position: 'center',
						buttons: {
							"CREAR": function() {
								$(this).html("").append($loading.clone())
								$(this).load(url, {cliente: cliente, host: host, servicio: servicio, estado: estado, integracion: 'mantis'});
								$(this).dialog('option', 'buttons', {
									"CERRAR": function() {
										$(this).dialog('destroy').remove();
									},									
								});								
							},
							"CERRAR": function() {
								$(this).dialog('destroy').remove();
							}
						},
						close: function(event, ui) {
							$(this).dialog('destroy').remove();
						}
					});	

					$(this).click(function() {
						$dialog.dialog('open');
						return false;						
					});
				
				}); // Fin $(this).unbind()
			}); // Fin $('.c_mantis').each()
		
		}); // Fin this.grid.store.on('load')
	},

	doRefresh: function(from, to) {
		if(this.grid && this.grid.store.loaded) {
			this.grid.store.load();
		}
	}
});
