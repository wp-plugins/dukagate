var dg_kopokopo = {
	process : function(r, return_url, invoice_id){
		jQuery.ajax({
			type: "POST",
			url: dg_js.ajaxurl,
			data: {action : "dg_kopokopo", status : r.check_transaction_response.check_transaction_result.status_code, invoice : invoice_id},
			success: function(response){
				window.location.href = return_url;
			}
		});
		return false;
	}
}