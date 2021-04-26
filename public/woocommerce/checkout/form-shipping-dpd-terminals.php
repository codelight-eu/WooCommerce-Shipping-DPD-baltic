<tr class="wc_shipping_dpd_terminals">
	<th><?php esc_html_e( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ) ?> <abbr class="required" title="required">*</abbr></th>
	<td>
		<select name="<?php echo esc_attr( $field_name ) ?>" id="<?php echo esc_attr( $field_id ) ?>" style="width: 100%;">
			<option value="" <?php selected( $selected, '' ); ?>><?php echo esc_html__( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ) ?></option>
			<?php foreach( $terminals as $group_name => $locations ) : ?>
				<optgroup label="<?php echo $group_name ?>">
					<?php foreach( $locations as $location ) : ?>
						<option data-cod="<?php echo $location->cod ?>" value="<?php echo esc_html( $location->parcelshop_id ) ?>" <?php selected( $selected, $location->parcelshop_id ); ?>><?php echo esc_html( $location->name ) ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
