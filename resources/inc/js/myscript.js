jQuery(function(){
    var html_action = '<div id="static_html_output" style="margin:15px 0;"><label class="selectit misc-pub-section"><input value="576" type="checkbox" name="static_output_html" value="true"> Estatizar em HTML</label></div>';
    if(jQuery('#post_status').val()=='publish'){
        jQuery('#submitdiv #submitpost #minor-publishing #misc-publishing-actions').append(html_action);
    }
    jQuery('.edit-tag-actions').prepend(html_action);
});