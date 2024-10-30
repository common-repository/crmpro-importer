<div class='wrap' >
	<h2>CRMPRO Importer Settings</h2>
	<p>Check your CRMPRO account in the settings area, under Profile Settings, you will see your Web Services access Key and your User ID. Enter these into the settings for the CRMPRO Importer plugin, and click the save button.  Once you click save, we will test the web services key just to make sure it's working.  If not then please contact support at CRMPRO.</p>
	<form method='post' >
		<table class='form-table' >
			<tbody>
				<tr>
					<th>
						<label for='cpi_webservice_key' >Web Service Key</label>
					</th>
					<td>
						<input type='text' class='regular-text' value='<?php _e( esc_attr( get_option( 'cpi_webservice_key' , '' ) ) ); ?>' name='cpi_webservice_key' id='cpi_webservice_key'  />&nbsp;&nbsp; <span class='description' >Enter the key that you can find in CRM Pro settings</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for='cpi_user_id' >User ID</label>
					</th>
					<td>
						<input type='text' class='regular-text' value='<?php _e( esc_attr( get_option( 'cpi_user_id' , '' ) ) ); ?>' name='cpi_user_id' id='cpi_user_id'  />&nbsp;&nbsp; <span class='description' >Enter your user ID that you can find in CRM Pro settings</span>
					</td>
				</tr>
			</tbody>
		</table>
		<input type='submit' class='button button-primary' value='Save settings' />
		<input type='hidden' value='cpi_settings_save' name='action' />
	</form>
</div>
