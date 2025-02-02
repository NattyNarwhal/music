<?php
use \OCA\Music\Utility\HtmlUtil;
HtmlUtil::printNgTemplate('navigationitem');
?>

<div id="app-navigation" ng-controller="NavigationController">
	<ul>
		<li navigation-item text="'Albums' | translate" destination="'#'"
			title="{{ albumCountText() }}" icon="'album'"></li>
		<li navigation-item text="'Folders' | translate" destination="'#/folders'"
			title="{{ folderCountText() }}" icon="'folder-nav'"></li>
		<li navigation-item text="'Genres' | translate" destination="'#/genres'"
			title="{{ genresCountText() }}" icon="'audiotrack'"></li>
		<li navigation-item text="'All tracks' | translate" destination="'#/alltracks'"
			title="{{ trackCountText() }}" icon="'library-music'"></li>
		<li class="app-navigation-separator"></li>
		<li navigation-item text="'Internet radio' | translate" destination="'#/radio'"
			title="{{ radioCountText() }}" icon="'radio'"></li>
		<li navigation-item text="'Podcasts' | translate" destination="'#/podcasts'"
			title="{{ podcastsCountText() }}" icon="'podcast'"></li>
		<li class="app-navigation-separator"></li>
		<li navigation-item text="'Smart playlist' | translate" destination="'#/smartlist'"
			title="{{ smartListTrackCountText() }}" icon="'smart-playlist'"></li>
		<li class="music-navigation-item" ui-on-drop="dropOnPlaylist($data, null)" drag-hover-class="drag-hover">
			<div id="new-playlist" class="music-navigation-item-content">
				<div class="icon-add" ng-click="startCreate()" ng-if="!newPlaylistTrackIds.length"></div>
				<div class="track-count-badge" ng-if="newPlaylistTrackIds.length">{{ newPlaylistTrackIds.length }}</div>
				<div id="create" class="app-navigation-noclose" ng-click="startCreate()" ng-hide="showCreateForm" translate>New Playlist</div>
				<div class="input-container" ng-show="showCreateForm">
					<input type="text" class="new-list" maxlength="256"
						placeholder="{{ 'New Playlist' | translate }}" on-enter="commitCreate()" ng-model="newPlaylistName" />
				</div>
				<div class="actions" ng-show="showCreateForm">
					<button class="action icon-checkmark app-navigation-noclose"
						ng-class="{ disabled: newPlaylistName.length == 0}" ng-click="commitCreate()"></button>
					<button class="action icon-close app-navigation-noclose" ng-click="closeCreate()"></button>
				</div>
			</div>
		</li>
		<li navigation-item
			playlist="playlist" text="playlist.name" destination="'#/playlist/' + playlist.id"
			ng-repeat="playlist in playlists"
			ui-on-drop="dropOnPlaylist($data, playlist)"
			drop-validate="allowDrop(playlist, $data)"
			drag-hover-class="drag-hover"
			title="{{ trackCountText(playlist) }}"
			icon="'playlist'"></li>
		<li id="music-nav-settings" ng-class="{active: currentView=='#/settings'}">
			<a class="" ng-click="navigateTo('#/settings')">
				<img class="svg" src="<?php HtmlUtil::printSvgPath('settings') ?>">
				{{ 'Settings' | translate }}
			</a>
		</li>
	</ul>

	<!-- a hidden button which may be programmatically clicked to collapse the navigation pane on the mobile layout -->
	<button id="hidden-close-app-navigation-button" style="display: none"></button>
</div>