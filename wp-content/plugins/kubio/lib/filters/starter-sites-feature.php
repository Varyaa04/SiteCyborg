<?php


if ( kubio_is_pro() ) {
	add_filter( 'kubio/starter-sites/enabled', '__return_true', 500 );
} else {
	if ( get_option( '_kubio_is_siteground_imported', false ) ) {
		add_filter( 'kubio/starter-sites/enabled', '__return_false', 0 );
	}
}
