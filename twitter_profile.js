var twitterProfileUpdate = function( data ){
	(function($){
		$(function(){
			var twitterProfile = function( data ){
				this.data = data;
				if( data.error == undefined ){
					this.profile = document.getElementById( 'TwitterProfile_' + data.screen_name );
				} else {
					var screen_name = this.data.request.match(/\?screen_name=([^&]+)/);
					this.profile = document.getElementById( 'TwitterProfile_' + screen_name[1] );
				}
				this.get = function( className ){
					var elements = new Array();
					var objects = this.profile.getElementsByTagName( "*" );
					for( var i = 0; i < objects.length; i ++ ){
						if( objects[i].className.indexOf( className, 0 ) != -1 ){
							elements.push( objects[i] );
						}
					}
					return elements;
				};
				this.each = function( elements, callback ){
					for( var i = 0; i < elements.length; i ++ ){
						callback( elements[i] );
					}
				};
				this.textFilter = function( text ){
					text = text.replace( /(https?:\/\/[a-zA-Z0-9.\/%#\?]+)/, '<a href="$1" target="_blank">$1</a>' );
					text = text.replace( /@([a-zA-Z0-9_]+)/, '<a href="http://twitter.com/$1" target="_blank">@$1</a>' );
					return text.replace( /#([^\s^　]+)/, '<a href="http://twitter.com/#search?q=$1" target="_blank">#$1</a>' );
				};
				this.numberFormat = function( number ){
					return number.toString().replace( /([0-9]+?)(?=(?:[0-9]{3})+$)/g , '$1,' );
				};
				this.update = function(){
					var tp = this;
					tp.each( tp.get( 'tp_profile_image' ), function( element ){ element.src = tp.data.profile_image_url; } );
					tp.each( tp.get( 'tp_name' ), function( element ){ element.innerHTML = tp.data.name; } );
					tp.each( tp.get( 'tp_screen_name' ), function( element ){ element.innerHTML = tp.data.screen_name; } );
					tp.each( tp.get( 'tp_time_zone' ), function( element ){ element.innerHTML = tp.data.time_zone; } );
					tp.each( tp.get( 'tp_location' ), function( element ){ element.innerHTML = tp.data.location; } );
					tp.each( tp.get( 'tp_description' ), function( element ){ element.innerHTML = tp.textFilter( tp.data.description ); } );
					tp.each( tp.get( 'tp_url' ), function( element ){ element.innerHTML = tp.textFilter( tp.data.url ); } );
					tp.each( tp.get( 'tp_latest_tweet' ), function( element ){ element.innerHTML = tp.textFilter( tp.data.status.text ); } );
					tp.each( tp.get( 'tp_status_count' ), function( element ){ element.innerHTML = tp.numberFormat( tp.data.statuses_count ); } );
					tp.each( tp.get( 'tp_friends_count' ), function( element ){ element.innerHTML = tp.numberFormat( tp.data.friends_count ); } );
					tp.each( tp.get( 'tp_followers_count' ), function( element ){ element.innerHTML = tp.numberFormat( tp.data.followers_count ); } );
				};
				this.delete = function(){
					this.profile.parentNode.removeChild( this.profile );
				};
			};
			
			var tp = new twitterProfile( data );
			if( data.error == undefined ){
				tp.update();
			} else {
				tp.delete();
			}
			
		});
	})(jQuery);
};
