var area_delivery = {
	save : function(area, rate, target){
		var a = jQuery('#'+area).val();
		var r = jQuery('#'+rate).val();
		var i = jQuery("#area_id").val();
		if(a.length > 0 && r.length > 0){
			jQuery.ajax({
				type: "POST",
				url: userSettings.ajaxurl,
				data: {'action' : 'dg_save_shipping_rate', 'area' : a, 'rate' : r, 'id' : i},
				success: function(response){
					if(response.success == 'true'){
						if(i == "" || i == " "){
							jQuery('#'+target).prepend(unescape(response.html));
						}
						else{
							jQuery('#area_'+i).html(a);
							jQuery('#rate_'+i).html(r);
						}
						jQuery('#'+area).val('');
						jQuery('#'+rate).val('');
						jQuery("#area_id").val('');
					}
				}
			});
		}
		return false;
	},
	
	edit : function(id, plugin_slug){
		jQuery('#'+plugin_slug+"_area").val(jQuery('#area_'+id).html());
		jQuery('#'+plugin_slug+"_rate").val(jQuery('#rate_'+id).html());
		jQuery("#area_id").val(id);
		
	},
	
	del : function(id){
		jQuery('#del_'+id).css('background-color','#ff0000');
		jQuery.ajax({
			type: "POST",
			url: userSettings.ajaxurl,
			data: {'action' : 'dg_del_area_delivery', 'id' : id},
			success: function(response){
				if(response.success == 'true'){
					jQuery('tr').remove('#del_'+id);
				}
			}
		});
	}
}