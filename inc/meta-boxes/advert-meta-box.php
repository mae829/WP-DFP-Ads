<?php
	// Check if options are on
	$lazyload_status	= wp_dfp_ads_get_option( 'lazy-load' ) ? true : false;
	$refresh_status		= wp_dfp_ads_get_option( 'refresh' ) ? true : false;
?>
<table class="table__meta-box">
	<tr>
		<td>
			<label for="advert-id">ID:</label>

			<input type="text" name="_advert_id" id="advert_id" value="<?php echo $advert_id ?>" class="regular-text" required="required">
			<p class="description">Unique ID to target ads within templates using the Wp_Ad_Dfp class function "display_ad".</p>
			<p class="description">Ad Slot will be generated based on this field.</p>
		</td>
	</tr>
	<tr>
		<td>
			<label for="advert-slot">Slot Name:</label>
			<input type="hidden" name="_advert_slot" id="advert-slot" value="<?php echo $slot; ?>">
			<input type="text" name="advert_slot_display" id="advert-slot-display" value="<?php echo $slot ?>" disabled="disabled" class="regular-text">
		</td>
	</tr>
	<tr>
		<td>
			<label for="advert-logic" style="<?php echo ( isset( $_GET[$this->admin_notice_key] ) ? 'color:#dc3232;' : '' ); ?>">Logic:</label>
			<textarea name="_advert_logic" id="advert-logic" class="regular-text" style="<?php echo ( isset( $_GET[$this->admin_notice_key] ) ? 'border-color:#dc3232;' : '' ); ?>"><?php echo $logic ?></textarea>
			<p class="description">Do NOT use the words "AND" or "OR" as operators.</p>
		</td>
	</tr>
	<tr>
		<td>
			<label for="advert-markup">Custom Ad Markup:</label>
			<textarea name="_advert_markup" rows="5" id="advert-markup" class="regular-text"><?php echo $markup; ?></textarea>
		</td>
	</tr>
	<?php if ( $lazyload_status ): ?>
	<tr>
		<td>
			<label for="advert-exclude-lazyload">Turn Lazy Load Off:</label>
			<input type="checkbox" name="_advert_exclude_lazyload" id="advert-exclude-lazyload" value="1" <?php checked( $lazyload ); ?>>
			<p class="description">Check this box to exclude this ad from lazy-load rendering.</p>
		</td>
	</tr>
	<?php endif; ?>
	<?php if ( $refresh_status ): ?>
	<tr>
		<td>
			<label for="advert-exclude-refresh">Turn Refresh Ad Off:</label>
			<input type="checkbox" name="_advert_exclude_refresh" id="advert-exclude-refresh" value="1" <?php checked( $refresh ); ?>>
			<p class="description">Check this box to exclude this ad from refreshing cycles on the page.</p>
		</td>
	</tr>
	<?php endif; ?>
</table>
