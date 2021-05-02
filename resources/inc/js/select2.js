jQuery(function(){

    jQuery('.select2-api-rlout').each(function(){
        if(jQuery(this).find('select').data('action_ajax')!=''){

            jQuery(this).find('select').select2({
                placeholder: "Buscar",
                minimumInputLength: 3,
                ajax: {
                    delay: 250,
                    dataType: 'json',
                    url: ajaxurl,
                    data: function (params) {
                        var query = {
                        action: jQuery(this).data('action_ajax'),
                        search: params.term,
                        }
                
                        return query;
                    }
                }
            });

        }else{
            jQuery(this).find('select').select2();
        }
        
    });
});