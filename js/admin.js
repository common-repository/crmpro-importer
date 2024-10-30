jQuery(document).ready(function( $ ){

	$(".cpi-status").each(function(){

		var $status = $(this),
			working = false,
			user_id = $status.closest("tr").attr("id").replace("user-",""),
			title = $status.attr("title");

		$status.bind("click.cpi",function(e){
			e.preventDefault();

			if( working ) return;

			working = true;

			$status.addClass("cpi-status-importing").removeClass("cpi-status-no").attr("title","Importing...");

			$.post( ajaxurl , { "user_id" : user_id, action : "cpi_import" } , function( response ){

				console.log( response )
				if( response.status == "OK" ){

					$status.addClass("cpi-status-yes").removeClass("cpi-status-importing").attr("title","Imported at " + response.time );
					//$status.unbind("click.cpi");

				}
				else {
					$status.addClass("cpi-status-no").removeClass("cpi-status-importing").attr("title", title );
					alert( response.error_msg );
				}

				working = false;
				
			}, "json" );


		});
	});

	$("#cpi-import-filter-yes").bind("click",function(){
		$("body").addClass("cpi-import-show-all");
	});

	$("#cpi-import-filter-no").bind("click",function(){
		$("body").removeClass("cpi-import-show-all");
	});

	var $user_roles_checkboxes = $("#cpi-user-roles input[type='checkbox']:gt(0)");

	$("#cpi-user-roles-all").bind("click",function(){

		var checked = this.checked;

		$user_roles_checkboxes.each(function(){
			this.checked = checked;
		});
	});

	$user_roles_checkboxes.bind('click',function(){

		if( $user_roles_checkboxes.filter(":checked").size() == $user_roles_checkboxes.size() ){
			$("#cpi-user-roles-all").get(0).checked = true;
		}
		else {
			$("#cpi-user-roles-all").get(0).checked = false;
		}
	});

	(function(){

		var $import_button = $("#cpi-import-button"),
			$cancel_button = $("#cpi-import-cancel-button"),
			$status = $("#cpi-import-status"),
			$message = $("#message"),
			$progressbar = $("#cpi-import-bar").progressbar(),
			$progressbar_percent = $("#cpi-import-bar-percent"),
 			$results,
			users_for_import = [],
 			import_index = 0,
 			total = 0,
 			success = 0,
 			failure = 0,
			working = false,
			$ajax;

		$cancel_button.bind("click",function(){

			$status.append("<p>Import cancelled.</p>");

			$status.find(".cpi-importing").addClass('cpi-importing-cancelled');

			$message.hide().empty();

			$ajax.abort();

 			stop_working();

		});

		$import_button.bind("click",function(e){

			if( working ) return;

			$status.empty();

			$message.hide().empty();

 			$progressbar.hide();

			var selected_roles = new Array();

			$user_roles_checkboxes.filter(":checked").each(function(){
				selected_roles.push( this.value );
			});

			if( selected_roles.length == 0 ){

				$status.append("<p>You need to select at least one role..</p>");
				return;
			}

			$status.append("<p>Preparing users for importing...</p>");

			$import_button.hide();

			$cancel_button.show();

			working = true;

			$ajax = $.post( ajaxurl,{ action : "cpi_get_users" , roles : selected_roles , all : ($("#cpi-import-filter-yes").is(":checked")?1:0) },function( response ){

				if( response ){

					$status.empty();

					if( response.users.length == 0 ){

						stop_working();
			 			$status.html("<p>Zero users found.</p>");
					}
					else {


						$status.html(
							"<p><strong>Total users:</strong> <span id='cpi-import-total-count' >"+response.users.length+"</span><br/>"+
							"<strong>User imported:</strong> <span id='cpi-import-success-count' >0</span><br/>"+
							"<strong>Imports failures:</strong> <span id='cpi-import-failure-count' >0</span></p>"+
							"<ul id='cpi-import-results' ></ul>" 
						);

						start_import( response.users );
					}



				}

			}, "json");

 		});

 		var start_import = function( users ){

			$results = $("#cpi-import-results");

			users_for_import = users;
			total = users_for_import.length;
			import_index = 0;
 			success = 0;
 			failure = 0;

 			$progressbar.progressbar( "value" , 0 );
			$progressbar_percent.html( "0%" );

 			$progressbar.show();

			import_next_user();


 		};

 		var import_next_user = function(){

 			if( users_for_import[ import_index ] ){
 				import_user( users_for_import[ import_index ] , function(){
 					import_index++;
 					import_next_user();
 				} );
 			}
 			else {
 				
 				finish_import();
 			}
 		};

 		var import_user = function( user , callback ){

 			var $item = $( "<li id='cpi-user-"+user.user_id+"' class='cpi-importing' >Importing "+user.name+"</li>" );

 			$results.append( $item );

 			$ajax = $.post( ajaxurl , { action : "cpi_import", user_id : user.user_id }, function( response ){

 				$item.removeClass("cpi-importing");

 				if( response.status == "OK" ){
 					$item.html( user.name + " imported.").addClass("cpi-import-success");
 					success++;
	 				$("#cpi-import-success-count").html( success );
 				}
 				else {
 					$item.html( "Error importing "+ user.name + ". ( "+ response.error_msg+" )").addClass("cpi-import-error");
 					failure++;
	 				$("#cpi-import-failure-count").html( failure );
 				}

	 			$progressbar.progressbar( "value", ( ( import_index + 1 ) / total ) * 100 );

				$progressbar_percent.html( Math.round( ( ( import_index + 1 ) / total ) * 1000 ) / 10 + "%" );

 				callback.call();

 			}, "json");
 		};

 		var stop_working = function(){

			 working = false;
 			$cancel_button.hide();
 			$import_button.show();
 		}


 		var finish_import = function(){
 			stop_working();
 			$results.append("<li>Import completed.</li>");
 			$message.html("<p><strong>All done! "+success+" user(s) were successfully imported and there were "+failure+" failures. </strong></p>").show();
 		}

	})();

});