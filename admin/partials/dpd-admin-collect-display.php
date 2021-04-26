<h2><?php _e( 'Where should we pick up your parcels?', 'woo-shipping-dpd-baltic' ); ?></h2>

<table class="form-table">
	<tbody>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Sender name', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_sender_name" type="text" maxlength="140" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Sender address', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_sender_street_address" type="text" maxlength="35" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Sender postcode', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_sender_postcode" type="text" maxlength="8" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Sender city', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_sender_city" type="text" maxlength="25" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Sender country', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-select">
            <select name="<?php echo $fields_prefix ?>_sender_country" class="form-control" required>
                <option value=""><?php _e( 'Select country', 'woo-shipping-dpd-baltic' ); ?></option>
				<?php foreach($countries as $code => $country): ?>
                    <option value="<?php echo $code ?>"><?php echo $country ?></option>
				<?php endforeach ?>
            </select>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Contact person phone number', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_sender_contact_phone_number" type="text" maxlength="20" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Contact person email address', 'woo-shipping-dpd-baltic' ); ?></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_sender_contact_email" type="text" maxlength="30">
		</td>
	</tr>
	</tbody>
</table>

<h2><?php _e( 'Where should we deliver your parcels?', 'woo-shipping-dpd-baltic' ); ?></h2>

<table class="form-table">
	<tbody>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Recipient name', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_recipient_name" type="text" maxlength="70" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Recipient address', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_recipient_street_address" type="text" maxlength="35" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Recipient postcode', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_recipient_postcode" type="text" maxlength="8" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Recipient city', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_recipient_city" type="text" maxlength="25" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Recipient country', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-select">
            <select name="<?php echo $fields_prefix ?>_recipient_country" class="form-control" required>
                <option value=""><?php _e( 'Select country', 'woo-shipping-dpd-baltic' ); ?></option>
				<?php foreach($countries as $code => $country): ?>
                    <option value="<?php echo $code ?>"><?php echo $country ?></option>
				<?php endforeach ?>
            </select>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Contact person phone number', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_recipient_contact_phone_number" maxlength="20" type="text" required>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Contact person email address', 'woo-shipping-dpd-baltic' ); ?></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_recipient_contact_email" maxlength="30" type="text">
		</td>
	</tr>
	</tbody>
</table>

<h2><?php _e( 'Details about your parcels/pallets', 'woo-shipping-dpd-baltic' ); ?></h2>

<table class="form-table">
	<tbody>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Enter the amount of parcels/pallets', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text flex-fields">
			<div>
                <label>
                    <?php _e( 'Parcels', 'woo-shipping-dpd-baltic' ); ?>
                    <input name="<?php echo $fields_prefix ?>_parcels_number" type="number" required>
                </label>
            </div>
			<div>
                <label>
                    <?php _e( 'Pallets', 'woo-shipping-dpd-baltic' ); ?>
                    <input name="<?php echo $fields_prefix ?>_pallets_number" type="number">
                </label>
            </div>
			<div>
                <label>
                    <?php _e( 'Total weight (kg)', 'woo-shipping-dpd-baltic' ); ?>
                    <input name="<?php echo $fields_prefix ?>_total_weight" type="number" step="any" required>
                </label>
            </div>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Additional information', 'woo-shipping-dpd-baltic' ); ?></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_additional_information" type="text" maxlength="30">
		</td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Provide your desired pickup date', 'woo-shipping-dpd-baltic' ); ?> <abbr>*</abbr></th>
		<td class="forminp forminp-text">
			<input name="<?php echo $fields_prefix ?>_pickup_date" value="<?php echo $date; ?>" class="dpd_datepicker_upcoming" type="text" required>
		</td>
	</tr>
	</tbody>
</table>

<p class="submit">
	<button name="save" class="button-primary woocommerce-save-button" id="request_collect" type="submit" value="Save changes"><?php _e( 'Request', 'woo-shipping-dpd-baltic' ); ?></button>
    <input type="hidden" name="action" value="dpd_order_collection_request">
</p>