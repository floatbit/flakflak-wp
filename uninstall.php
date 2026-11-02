<?php
/** Remove only this plugin's settings; hosted FlakFlak feedback is untouched. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'flakflak_wp_settings' );
