<div class='wrap' >
	<h2>CRMPRO Import</h2>
	<div id="message" class="updated" style="display:none"></div>

	<p>Don't close this window once import starts, please wait for it to finish; may take up to 5-10 minutes depending upon number of users, but typically goes at a rate of 10,000 per minute.</p>

	<div id="cpi-import-bar" >
		<div id="cpi-import-bar-percent"></div>
	</div>

	<div id='cpi-import-wrapper' >
		<p>
			<strong>Show already imported users ?</strong><br/>
			<span><input name='cpi-import-filter' type='radio' id='cpi-import-filter-yes' value='1' /> Yes</span> &nbsp;&nbsp;
			<span><input name='cpi-import-filter' type='radio' id='cpi-import-filter-no' value='0' checked='checked' /> No</span> 
			<br/><br/>
			<strong>Select what user roles you want to import.</strong> <br/>
			<ul id='cpi-user-roles' >
				<li>
					<label>
						<input type='checkbox' id='cpi-user-roles-all' />
						<span> All (<span><?php echo $this->get_remaining_users_count(); ?></span><span class='cpi-import-all'><?php echo $this->get_all_users_count(); ?></span>)</span>
					</label>
				</li>
				<?php 
					foreach( $this->get_user_roles() as $user_role ) : 
						echo sprintf("<li><label><input type='checkbox' name='cpi-user-roles[]' value='%s' /><span> %s (<span>%s</span><span class='cpi-import-all'>%s</span>)</span></label></li>",esc_attr( $user_role), $user_role, $this->get_remaining_users_count( $user_role ) , $this->get_all_users_count( $user_role ) );
					endforeach; 
				?>
			</ul>
			<input type='button' class='button button-primary' value='Import' id='cpi-import-button' />
			<input type='button' class='button button-primary' value='Cancel' id='cpi-import-cancel-button' />
		</p>

		<div id='cpi-import-status' >

		</div>
	</div>
</div>
