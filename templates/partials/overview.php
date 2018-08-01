<div id="overview"  ng-show="!loading && !loadingCollection">
	<div class="artist-area" ng-repeat="artist in artists | limitTo: incrementalLoadLimit">
		<h1 id="artist-{{ ::artist.id }}">
			<span ng-click="playArtist(artist)" ui-draggable="true" drag="getDraggable('artist', artist)">
				<span >{{ ::artist.name }}</span>
				<img class="play svg" alt="{{ ::('Play' | translate) }}" src="<?php p(OCP\Template::image_path('music', 'play-big.svg')) ?>"/>
			</span>
		</h1>
		<div class="album-area" ng-repeat="album in artist.albums">
			<h2 id="album-{{ ::album.id }}">
				<div ng-click="playAlbum(album)"
					 title="{{ ::(album.name + decoratedYear(album)) }}"
					 ui-draggable="true" drag="getDraggable('album', album)">
					<span>{{ ::album.name }}</span>
					<span ng-if="::album.year" class="muted">{{ ::decoratedYear(album) }}</span>
				</div>
			</h2>
			<div ng-click="playAlbum(album)" class="albumart" cover="{{ album.cover }}" albumart="{{ album.name }}"></div>
			<img ng-click="playAlbum(album)" class="play overlay svg" alt="{{ ::('Play' | translate) }}"
				 src="<?php p(OCP\Template::image_path('music', 'play-big.svg')) ?>" />
			<track-list
					tracks="album.tracks"
					get-track-data="getTrackData"
					play-track="playTrack"
					show-track-details="showSidebar"
					get-draggable="getTrackDraggable"
					collapse-limit="6"
					more-text="'Show all {{ album.tracks.length }} songs …' | translate"
					less-text="'Show less …' | translate"
					details-text="'Details' | translate">
			</track-list>
		</div>
	</div>

	<alphabet-navigation ng-if="artists" targets="alphabetNavigationTargets"></alphabet-navigation>
</div>
