<h2><?php _e( 'Manifests', 'woo-shipping-dpd-baltic' ); ?></h2>

<table class="form-table">
	<tbody>
	<tr valign="top">
		<td colspan="2">
			<table class="wc_gateways widefat" cellspacing="0">
				<thead>
				<tr>
					<th><?php _e( 'File name', 'woo-shipping-dpd-baltic' ); ?></th>
					<th><?php _e( 'Date', 'woo-shipping-dpd-baltic' ); ?></th>
					<th class="action"></th>
				</tr>
				</thead>
				<tbody class="ui-sortable">
				<?php foreach( $results as $result ) : ?>
				<tr>
					<td>manifest_<?php echo str_replace( '-', '_', $result->date ); ?>.pdf</td>
					<td width="20%"><?php echo $result->date ?></td>
					<td width="1%">
						<a class="button alignright" type="submit" href="<?php echo get_admin_url() ?>admin.php?page=wc-settings&tab=dpd&section=manifests&download_manifest=<?php echo $result->id; ?>"><?php _e( 'Download', 'woo-shipping-dpd-baltic' ); ?></a>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</td>
	</tr>
	</tbody>
</table>