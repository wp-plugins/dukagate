var dukagate = {
	delete_order : function(id){
		jQuery('#order_'+id).css('background-color','#ff0000');
		jQuery.ajax({
			type: "POST",
			url: userSettings.ajaxurl,
			data: {'action' : 'dg_delete_order_log', 'id' : id},
			success: function(response){
				if(response.success == 'true'){
					jQuery('tr').remove('#order_'+id);
				}
			}
		});
	},

	
	email_update : function(formid){
		jQuery("#"+formid).submit(function() {
			jQuery("#"+formid+"_status").html('Processing......');
			var form_values = jQuery(this).serialize();
			jQuery.ajax({
				type: "POST",
				url: userSettings.ajaxurl,
				data: form_values,
				success: function(response){
					if(response.success == 'true')
						jQuery("#"+formid+"_status").html(response.response);
				}
			});
			return false;
		});
	},
	
	change_order_status : function(id){
		var status = jQuery.trim(jQuery("#dg_order_status").html());
		jQuery.ajax({
			type: "POST",
			url: userSettings.ajaxurl,
			data: {'id' : id, 'stat' : status, 'action' : 'dg_change_order_log'},
			success: function(response){
				if(response.success == 'true')
					jQuery("#dg_order_status").html(response.status);
			}
		});
		
	},
	
	order_csv_export : function(id){
		var request_aparms = "ajax=true&dg_order_export=export&id="+id;
		window.location.href = dpsc_url+'/index.php?' + request_aparms;
		return false;
	},
	
	add_variation : function(id){
		var var_prod = jQuery("#var_prod").val();
		var var_type = jQuery("#var_type").val();
		var var_val = jQuery("#var_val").val();
		jQuery.ajax({
			type: "POST",
			url: userSettings.ajaxurl,
			data: {'product' : var_prod, 'type' : var_type, 'value' : var_val, 'action' : 'dg_change_variation'},
			success: function(response){
				if(response.success == 'true')
					jQuery('#'+id).prepend(unescape(response.html));
			}
		});
	},
	
	del_variation : function(){
	
	}
}