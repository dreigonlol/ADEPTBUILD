<?php
/**
 * Search form.
 *
 * @package Adeptbuild
 */

$ab_search_id = 'ab-search-' . wp_unique_id();
?>
<form role="search" method="get" class="ab-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>"
	style="display:flex; gap:10px;">
	<label class="screen-reader-text" for="<?php echo esc_attr( $ab_search_id ); ?>">
		<?php esc_html_e( 'Search the site', 'adeptbuild' ); ?>
	</label>
	<input
		type="search"
		id="<?php echo esc_attr( $ab_search_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search…', 'adeptbuild' ); ?>">
	<button type="submit" class="ast-button ab-btn--sm"><?php esc_html_e( 'Search', 'adeptbuild' ); ?></button>
</form>
