var dukagate ={

	group_update_cart : function(formid, ajax_cart){
		
		jQuery("#"+formid).submit(function() {
			var selected_inputs = [];
			jQuery(".dg_product_holder input:radio").each(function() {
				if (jQuery(this).is(':checked')){
					selected_inputs.push(jQuery(this).val());
				}
			});
			jQuery(".dg_product_holder input:checkbox").each(function() {
				if (jQuery(this).is(':checked')){
					selected_inputs.push(jQuery(this).val());
				}
			});
			var form_values = jQuery("#"+formid).serialize();
			var btn = jQuery('.dg_make_payment', jQuery(this));
			btn.val(dg_js.processing + "......");
			jQuery.ajax({
				type: "POST",
				url: dg_js.ajaxurl,
				data: form_values+'&children='+selected_inputs.toString(),
				success: function(response){
					if(response.success == 'true'){
						if(ajax_cart == 'true'){
							btn.val(dg_js.added_to_cart);
							if(jQuery('#mini_cart_total').length > 0){
								jQuery('#mini_cart_total').html(response.total);
							}
							if(jQuery('#dukagate_cart_widget').length > 0){
								jQuery('#dukagate_cart_widget').html(response.html);
							}
							btn.val(dg_js.add_to_cart);
						}
						else{
							window.location.href = response.url;
						}
					}
				}
			});
			return false;
		});
	},
	
	update_cart : function(formid, ajax_cart){
		jQuery("#"+formid).submit(function() {
			var btn = jQuery('.dg_make_payment', jQuery(this));
			btn.val(dg_js.processing + "......");
			var form_values = jQuery(this).serialize();
			jQuery.ajax({
				type: "POST",
				url: dg_js.ajaxurl,
				data: form_values,
				success: function(response){
					if(response.success == 'true'){
						if(ajax_cart == 'true'){
							btn.val(dg_js.added_to_cart);
							if(jQuery('#mini_cart_total').length > 0){
								jQuery('#mini_cart_total').html(response.total);
							}
							if(jQuery('#dukagate_cart_widget').length > 0){
								jQuery('#dukagate_cart_widget').html(response.html);
							}
							btn.val(dg_js.add_to_cart);
						}
						else{
							window.location.href = response.url;
						}
					}
				}
			});
			return false;
		});
	},
	
	update_quantity : function(quantity,product_id){
		jQuery.ajax({
			type: "POST",
			url: dg_js.ajaxurl,
			data: {'product_id' : product_id, 'quantity' : jQuery('#'+quantity).val(), 'action' : 'dg_update_cart'},
			success: function(response){
				if(response.success == 'true')
					window.location.href = response.url;
			}
		});
	},
	
	process_shipping : function(formid){
		jQuery("#"+formid).submit(function() {
			var form_values = jQuery(this).serialize();
			jQuery('#shipping_submit_input').val(dg_js.processing + "......");
			jQuery.post(dg_js.ajaxurl+'/index.php', form_values, function(data){
				window.location.reload();
			});
			return false;
		});
	},
	
	checkout : function(form){
		var form_values = jQuery(form).serialize();
		jQuery('#dg_process_payment_form').val(dg_js.processing + "......");
		jQuery.post(dg_js.ajaxurl+'/index.php', form_values, function(data){
            eval(data);
        });
	},
	
	empty_cart : function(){
		jQuery.ajax({
			type: "POST",
			url: dg_js.ajaxurl,
			data: {'action' : 'dg_empty_cart'},
			success: function(response){
				window.location.reload();
			}
		});
	},
	
	validate_discount : function(input_id){
		var input_value = jQuery('#'+input_id).val();
		jQuery('#'+input_id).css('border-color', '#000000');
		if(input_value.length > 0){
			jQuery.ajax({
				type: "POST",
				url: dg_js.ajaxurl,
				data: {'action' : 'dg_validate_discount_code', 'dg_code' : input_value},
				success: function(response){
					if(response.response == 'valid'){
						window.location.reload();
					}else{
						jQuery('#dg_disc_reponse').html(response.response);
					}
				}
			});
		}else{
			jQuery('#'+input_id).focus();
			jQuery('#'+input_id).css('border-color', '#ff0000');
		}
	},
	
	add_price : function(price, target, id, fixed_price){
		var elem = jQuery('#'+id);
		var org_price = jQuery('#'+target).val();
		var final_price = 0.00;
		if(fixed_price == 'checked'){
			if(elem.is(':checked')){
				if(fixed_price == 'checked'){
					final_price = parseInt(org_price) + parseInt(price);
				}
			}else{
				if(fixed_price == 'checked'){
					final_price = parseInt(org_price) - parseInt(price);
				}
			}
			jQuery('#'+target).val(final_price);
		}
	},
	
	add_quantity : function(quantity, target, math){
		if(math == 'uniq'){
			jQuery('#'+target).val(parseInt(quantity));
		}
		else{
			var org_q = jQuery('#'+target).val();
			var final_q = 0;
			final_q = parseInt(org_q) + parseInt(quantity);
			jQuery('#'+target).val(final_q);
		}
	}
}