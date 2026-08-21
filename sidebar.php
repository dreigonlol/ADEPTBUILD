<?php
/**
 * Blog sidebar.
 *
 * @package Adeptbuild
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>

<aside id="secondary" class="ab-sidebar" role="complementary">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
