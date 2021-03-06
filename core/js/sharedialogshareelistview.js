/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	if (!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE =
			'<ul id="shareWithList">' +
			'{{#each sharees}}' +
			'    {{#if isCollection}}' +
			'    <li data-collection="{{collectionID}}">{{text}}</li>' +
			'    {{/if}}' +
			'    {{#unless isCollection}}' +
			'    <li data-share-type="{{shareType}}" data-share-with="{{shareWith}}" title="{{shareWith}}">' +
			'        <a href="#" class="unshare"><img class="svg" alt="{{unshareLabel}}" title="{{unshareLabel}}" src="{{unshareImage}}" /></a>' +
			'        {{#if avatarEnabled}}' +
			'        <div class="avatar {{#if modSeed}}imageplaceholderseed{{/if}}" data-username="{{shareWith}}" {{#if modSeed}}data-seed="{{shareWith}} {{shareType}}"{{/if}}></div>' +
			'        {{/if}}' +
			'        <span class="username">{{shareWithDisplayName}}</span>' +
			'        {{#if mailPublicNotificationEnabled}} {{#unless isRemoteShare}}' +
			'        <label><input type="checkbox" name="mailNotification" class="mailNotification" {{#if wasMailSent}}checked="checked"{{/if}} />{{notifyByMailLabel}}</label>' +
			'        {{/unless}} {{/if}}' +
			'        {{#if isResharingAllowed}} {{#if sharePermissionPossible}} {{#unless isRemoteShare}}' +
			'        <label><input id="canShare-{{shareWith}}" type="checkbox" name="share" class="permissions" {{#if hasSharePermission}}checked="checked"{{/if}} data-permissions="{{sharePermission}}" />{{canShareLabel}}</label>' +
			'        {{/unless}} {{/if}} {{/if}}' +
			'        {{#if editPermissionPossible}}' +
			'        <label><input id="canEdit-{{shareWith}}" type="checkbox" name="edit" class="permissions" {{#if hasEditPermission}}checked="checked"{{/if}} />{{canEditLabel}}</label>' +
			'        {{/if}}' +
			'        {{#unless isRemoteShare}}' +
			'        <a href="#" class="showCruds"><img class="svg" alt="{{crudsLabel}}" src="{{triangleSImage}}"/></a>' +
			'        <div class="cruds hidden">' +
			'            {{#if createPermissionPossible}}' +
			'            <label><input id="canCreate-{{shareWith}}" type="checkbox" name="create" class="permissions" {{#if hasCreatePermission}}checked="checked"{{/if}} data-permissions="{{createPermission}}"/>{{createPermissionLabel}}</label>' +
			'            {{/if}}' +
			'            {{#if updatePermissionPossible}}' +
			'            <label><input id="canUpdate-{{shareWith}}" type="checkbox" name="update" class="permissions" {{#if hasUpdatePermission}}checked="checked"{{/if}} data-permissions="{{updatePermission}}"/>{{updatePermissionLabel}}</label>' +
			'            {{/if}}' +
			'            {{#if deletePermissionPossible}} {{#unless isRemoteShare}}' +
			'            <label><input id="canDelete-{{shareWith}}" type="checkbox" name="delete" class="permissions" {{#if hasDeletePermission}}checked="checked"{{/if}} data-permissions="{{deletePermission}}"/>{{deletePermissionLabel}}</label>' +
			'            {{/unless}} {{/if}}' +
			'        </div>' +
			'        {{/unless}}' +
			'    </li>' +
			'    {{/unless}}' +
			'{{/each}}' +
			'</ul>'
		;

	/**
	 * @class OCA.Share.ShareDialogShareeListView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the sharee list part in the GUI of the share dialogue
	 *
	 */
	var ShareDialogShareeListView = OC.Backbone.View.extend({
		/** @type {string} **/
		id: 'shareDialogLinkShare',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {Function} **/
		_template: undefined,

		/** @type {boolean} **/
		showLink: true,

		/** @type {object} **/
		_collections: {},

		events: {
			'click .unshare': 'onUnshare',
			'click .permissions': 'onPermissionChange',
			'click .showCruds': 'onCrudsToggle',
			'click .mailNotification': 'onSendMailNotification'
		},

		initialize: function(options) {
			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}

			var view = this;
			this.model.on('change:shares', function() {
				view.render();
			});
		},

		processCollectionShare: function(shareIndex) {
			var type = this.model.getCollectionType(shareIndex);
			var id = this.model.getCollectionPath(shareIndex);
			if(type !== 'file' && type !== 'folder') {
				id = this.model.getCollectionSource(shareIndex);
			}
			var displayName = this.model.getShareWithDisplayName(shareIndex);
			if(!_.isUndefined(this._collections[id])) {
				this._collections[id].text = this._collections[id].text + ", " + displayName;
			} else {
				this._collections[id] = {};
				this._collections[id].text = t('core', 'Shared in {item} with {user}', {'item': id, user: displayName});
				this._collections[id].id = id;
				this._collections[id].isCollection = true;
			}
		},

		/**
		 *
		 * @param {OC.Share.Types.ShareInfo} shareInfo
		 * @returns {object}
		 */
		getShareeObject: function(shareIndex) {
			var shareWith = this.model.getShareWith(shareIndex);
			var shareWithDisplayName = this.model.getShareWithDisplayName(shareIndex);
			var shareType = this.model.getShareType(shareIndex);

			var hasPermissionOverride = {};
			if (shareType === OC.Share.SHARE_TYPE_GROUP) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote') + ')';
				hasPermissionOverride = {
					createPermissionPossible: true,
					updatePermissionPossible: true
				};
			}

			return _.extend(hasPermissionOverride, {
				hasSharePermission: this.model.hasSharePermission(shareIndex),
				hasEditPermission: this.model.hasEditPermission(shareIndex),
				hasCreatePermission: this.model.hasCreatePermission(shareIndex),
				hasUpdatePermission: this.model.hasUpdatePermission(shareIndex),
				hasDeletePermission: this.model.hasDeletePermission(shareIndex),
				wasMailSent: this.model.notificationMailWasSent(shareIndex),
				shareWith: shareWith,
				shareWithDisplayName: shareWithDisplayName,
				shareType: shareType,
				modSeed: shareType !== OC.Share.SHARE_TYPE_USER,
				isRemoteShare: shareType === OC.Share.SHARE_TYPE_REMOTE
			});
		},

		getShareeList: function() {
			var universal = {
				avatarEnabled: this.configModel.areAvatarsEnabled(),
				mailPublicNotificationEnabled: this.configModel.isMailPublicNotificationEnabled(),
				notifyByMailLabel: t('core', 'notify by email'),
				unshareLabel: t('core', 'Unshare'),
				unshareImage: OC.imagePath('core', 'actions/delete'),
				canShareLabel: t('core', 'can share'),
				canEditLabel: t('core', 'can edit'),
				createPermissionLabel: t('core', 'create'),
				updatePermissionLabel: t('core', 'change'),
				deletePermissionLabel: t('core', 'delete'),
				crudsLabel: t('core', 'access control'),
				triangleSImage: OC.imagePath('core', 'actions/triangle-s'),
				isResharingAllowed: this.configModel.get('isResharingAllowed'),
				sharePermissionPossible: this.model.sharePermissionPossible(),
				editPermissionPossible: this.model.editPermissionPossible(),
				createPermissionPossible: this.model.createPermissionPossible(),
				updatePermissionPossible: this.model.updatePermissionPossible(),
				deletePermissionPossible: this.model.deletePermissionPossible(),
				sharePermission: OC.PERMISSION_SHARE,
				createPermission: OC.PERMISSION_CREATE,
				updatePermission: OC.PERMISSION_UPDATE,
				deletePermission: OC.PERMISSION_DELETE
			};

			this._collections = {};

			if(!this.model.hasUserShares()) {
				return [];
			}

			var shares = this.model.get('shares');
			var list = [];
			for(var index = 0; index < shares.length; index++) {
				if(this.model.isCollection(index)) {
					this.processCollectionShare(index);
				} else {
					// first empty {} is necessary, otherwise we get in trouble
					// with references
					list.push(_.extend({}, universal, this.getShareeObject(index)));
				}
			}
			list = _.union(_.values(this._collections), list);

			return list;
		},

		render: function() {
			var shareeListTemplate = this.template();
			this.$el.html(shareeListTemplate({
				sharees: this.getShareeList()
			}));

			if(this.configModel.areAvatarsEnabled()) {
				this.$el.find('.avatar').each(function() {
					var $this = $(this);
					if ($this.hasClass('imageplaceholderseed')) {
						$this.css({width: 32, height: 32});
						$this.imageplaceholder($this.data('seed'));
					} else {
						$this.avatar($this.data('username'), 32);
					}
				});
			}

			this.delegateEvents();

			return this;
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function () {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template;
		},

		onUnshare: function(event) {
			var $element = $(event.target);

			if($element.hasClass('icon-loading-small')) {
				// in process
				return;
			}
			$element.empty().addClass('icon-loading-small');

			var $li = $element.closest('li');
			var shareType = $li.data('share-type');
			var shareWith = $li.attr('data-share-with');

			this.model.removeShare(shareType, shareWith);

			return false;
		},

		onPermissionChange: function(event) {
			var $element = $(event.target);
			var $li = $element.closest('li');
			var shareType = $li.data('share-type');
			var shareWith = $li.attr('data-share-with');

			// adjust checkbox states
			var $checkboxes = $('.permissions', $li).not('input[name="edit"]').not('input[name="share"]');
			var checked;
			if ($element.attr('name') === 'edit') {
				checked = $element.is(':checked');
				// Check/uncheck Create, Update, and Delete checkboxes if Edit is checked/unck
				$($checkboxes).attr('checked', checked);
			} else {
				var numberChecked = $checkboxes.filter(':checked').length;
				checked = numberChecked > 0;
				$('input[name="edit"]', $li).attr('checked', checked);
			}

			var permissions = OC.PERMISSION_READ;
			$('.permissions', $li).not('input[name="edit"]').filter(':checked').each(function(index, checkbox) {
				permissions |= $(checkbox).data('permissions');
			});

			this.model.setPermissions(shareType, shareWith, permissions);
		},

		onCrudsToggle: function(event) {
			var $target = $(event.target);
			$target.closest('li').find('.cruds').toggleClass('hidden');
			return false;
		},

		onSendMailNotification: function(event) {
			var $target = $(event.target);
			var $li = $(event.target).closest('li');
			var shareType = $li.data('share-type');
			var shareWith = $li.attr('data-share-with');

			this.model.sendNotificationForShare(shareType, shareWith, $target.is(':checked'));
		}
	});

	OC.Share.ShareDialogShareeListView = ShareDialogShareeListView;

})();
