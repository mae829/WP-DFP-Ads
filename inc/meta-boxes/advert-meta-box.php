<table class="wpdfpads__meta-box">
	<tr>
		<td>
			<label for="advert-id">ID:</label>

			<p class="description">Unique ID to target ads within templates using the Wp_Ad_Dfp class function "display_ad".</p>
			<p class="description">Ad Slot will be generated based on this field.</p>

			<input type="text" name="advert_id" id="advert_id" value="<?php echo $advert_id ?>" class="regular-text">
		</td>
	</tr>
	<tr>
		<td>
			<label for="advert-slot">Slot Name:</label>
			<input type="hidden" name="advert_slot" id="advert-slot" value="<?php echo $slot; ?>">
			<input type="text" name="advert_slot_display" id="advert-slot-display" value="<?php echo $slot ?>" disabled="disabled" class="regular-text">
		</td>
	</tr>
	<tr>
		<td>
			<label for="advert-logic" style="<?php echo ( isset( $_GET[$this->admin_notice_key] ) ? 'color:#dc3232;' : '' ); ?>">Logic:</label>
			<textarea name="advert_logic" id="advert-logic" class="large-text" style="<?php echo ( isset( $_GET[$this->admin_notice_key] ) ? 'border-color:#dc3232;' : '' ); ?>"><?php echo $logic ?></textarea>
			<p class="description">Do NOT use the words "AND" or "OR" as operators.</p>
		</td>
	</tr>
	<tr>
		<td>
			<label for="advert-markup">Custom Ad Markup:</label>
			<textarea name="advert_markup" rows="5" id="advert-markup" class="large-text"><?php echo $markup; ?></textarea>
		</td>
	</tr>
</table>
