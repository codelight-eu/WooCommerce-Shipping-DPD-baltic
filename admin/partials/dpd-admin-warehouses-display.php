<div class="warehouses-repeater" style="margin-top: 1rem;">
	<div data-repeater-list="warehouses">
		<?php if ( ! empty( $warehouses ) ) : ?>
            <?php foreach( $warehouses as $warehouse ) : $wh = $warehouse['option_value']; $wh_id = $warehouse['option_id']; ?>
                <div data-repeater-item>
                    <input type="text" name="name" placeholder="<?php esc_attr_e( 'Name', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" value="<?php echo $wh['name']; ?>" required>
                    <input type="text" name="address" placeholder="<?php esc_attr_e( 'Address', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" value="<?php echo $wh['address']; ?>" required>
                    <input type="text" name="postcode" placeholder="<?php esc_attr_e( 'Postcode', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" value="<?php echo $wh['postcode']; ?>" required>
                    <input type="text" name="city" placeholder="<?php esc_attr_e( 'City', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" value="<?php echo $wh['city']; ?>" required>
                    <select name="country" class="select2" required>
                        <option value=""><?php _e( 'Select country', 'woo-shipping-dpd-baltic' ); ?></option>
                        <?php foreach( $countries as $code => $country ) : ?>
                            <option value="<?php echo $code; ?>" <?php echo ( $code == $wh['country'] ? 'selected' : '' ); ?>><?php echo $country; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="contact_person" placeholder="<?php esc_attr_e( 'Contact person', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" value="<?php echo $wh['contact_person']; ?>" required>
                    <input type="text" name="phone" placeholder="<?php esc_attr_e( 'Phone', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" value="<?php echo $wh['phone']; ?>" required>
                    <button data-repeater-delete type="button" data-optionkey="<?php echo $wh_id; ?>"><?php _e( 'Delete', 'woo-shipping-dpd-baltic' ); ?></button>
                </div>
            <?php endforeach; ?>
		<?php else : ?>
			<div data-repeater-item>
				<input type="text" name="name" placeholder="<?php esc_attr_e( 'Name', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" required>
				<input type="text" name="address" placeholder="<?php esc_attr_e( 'Address', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" required>
				<input type="text" name="postcode" placeholder="<?php esc_attr_e( 'Postcode', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" required>
				<input type="text" name="city" placeholder="<?php esc_attr_e( 'City', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" required>
				<select name="country" class="form-control" required>
					<option value=""><?php _e( 'Select country', 'woo-shipping-dpd-baltic' ); ?></option>
					<?php foreach( $countries as $code => $country ) : ?>
						<option value="<?php echo $code; ?>"><?php echo $country; ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="contact_person" placeholder="<?php esc_attr_e( 'Contact person', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" required>
				<input type="text" name="phone" placeholder="<?php esc_attr_e( 'Phone', 'woo-shipping-dpd-baltic' ); ?>" class="form-control" required>
				<input data-repeater-delete type="button" value="<?php esc_attr_e( 'Delete', 'woo-shipping-dpd-baltic' ); ?>">
			</div>
		<?php endif; ?>
	</div>
	<input data-repeater-create type="button" value="<?php esc_attr_e( 'Add', 'woo-shipping-dpd-baltic' ); ?>">
</div>