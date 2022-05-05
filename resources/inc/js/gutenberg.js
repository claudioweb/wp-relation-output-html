(function(plugins, editPost, element, components, compose, data) {
	const el = element.createElement;
	const { registerPlugin } = plugins;
	const { unregisterPlugin } = plugins;
	const { getPlugin } = plugins;
	const { PluginPostStatusInfo } = editPost;
	const { CheckboxControl } = components;
	const { ExternalLink } = components;
    const { withSelect } = data;

    var MetaTextControl = compose.compose(
        withSelect(function(select, props) {
            return {
                metaValue: select('core/editor').getEditedPostAttribute('meta')[props.metaKey]
            }
        }))(function(props) {
            
            let [isChecked, setChecked] = element.useState(true);

            return el(CheckboxControl, {
                    metaKey: '_static_output_html',
                    label: 'Gerar página estática',
                    checked: isChecked,
                    onChange: () => {
                        setChecked(!isChecked);
                        wp.data.dispatch('core/editor').editPost({meta: {_static_output_html: !isChecked}});
                        var meta = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'meta' );
                    },
                }
            );
        }
    );
    
    const Output = () => {
        return el(PluginPostStatusInfo, {},
            el(MetaTextControl),
        );
    }

    function isSavingPost() {

        // State data necessary to establish if a save is occuring.
        const isSaving = wp.data.select('core/editor').isSavingPost() || wp.data.select('core/editor').isAutosavingPost();
        const isSaveable = wp.data.select('core/editor').isEditedPostSaveable();
        const isPostSavingLocked = wp.data.select('core/editor').isPostSavingLocked();
        const hasNonPostEntityChanges = wp.data.select('core/editor').hasNonPostEntityChanges();
        const isAutoSaving = wp.data.select('core/editor').isAutosavingPost();
        const isButtonDisabled = isSaving || !isSaveable || isPostSavingLocked;
        
        // Reduces state into checking whether the post is saving and that the save button is disabled.
        const isBusy = !isAutoSaving && isSaving;
        const isNotInteractable = isButtonDisabled && ! hasNonPostEntityChanges;
        
        return isBusy && isNotInteractable;
    }
    // Current saving state. isSavingPost is defined above.
    var wasSaving = isSavingPost();

    wp.data.subscribe( function () {

        var editor_link = jQuery(".editor-post-link");
        if(editor_link.length>0){
            
            jQuery('.clone_rlout').remove();

            var parent_container = editor_link.closest('.components-panel__body');
            var label_container = parent_container.find(".edit-post-post-link__preview-label");
            
            label_container.text('ver Post wordpress:');
            var cloned_label = label_container.clone();
            cloned_label.addClass('clone_rlout');
            parent_container.append(cloned_label.text('Ver Post estático:'));

            var spaces_br = '<br><br>';
            cloned_label.prepend(spaces_br);

            var link_static = jQuery('input[name="static_url_rlout"]').val();

            var external_container = parent_container.find('.edit-post-post-link__preview-link-container').clone();
            external_container.addClass('clone_rlout');
            var post_name = external_container.find('.edit-post-post-link__link-post-name').text();
            external_container.find('a').attr('href', link_static + post_name);
            external_container.find('.edit-post-post-link__link-prefix').text(link_static);
            parent_container.append(external_container);
        }
        

        // New saving state
        let isSaving = isSavingPost();

        // It is done saving if it was saving and it no longer is.
        let isDoneSaving = wasSaving && !isSaving;

        // Update value for next use.
        wasSaving = isSaving;

        var old_format = wp.data.select( 'core/editor' ).getCurrentPostAttribute('status');
        var newFormat = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
        var plugin_relation = getPlugin('relation-output');
        

        if(newFormat=='publish' && typeof plugin_relation=='undefined' || isDoneSaving){
            if(old_format!='auto-draft'){
 			    wp.data.dispatch('core/editor').editPost({meta: {_static_output_html: true}});
            }
            unregisterPlugin('relation-output');
            registerPlugin('relation-output', { render: Output });

        }else if(newFormat!='publish' && typeof plugin_relation!='undefined'){
            unregisterPlugin('relation-output');
        }

    });

}) (
	window.wp.plugins,
	window.wp.editPost,
	window.wp.element,
	window.wp.components,
    window.wp.compose,
    window.wp.data,
);