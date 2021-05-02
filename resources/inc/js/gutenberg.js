(function(plugins, editPost, element, components, compose, data) {
	const el = element.createElement;
	const { registerPlugin } = plugins;
	const { unregisterPlugin } = plugins;
	const { getPlugin } = plugins;
	const { PluginPostStatusInfo } = editPost;
	const { CheckboxControl } = components;
    const { withSelect } = data;

    var MetaTextControl = compose.compose(
        withSelect(function(select, props) {
            return {
                metaValue: select('core/editor').getEditedPostAttribute('meta')[props.metaKey],
            }
        }))(function(props) {
            let [isChecked, setChecked] = element.useState(true);
 			wp.data.dispatch('core/editor').editPost({meta: {_static_output_html: true}});
		
            return el(CheckboxControl, {
                    metaKey: '_static_output_html',
                    label: 'Gerar página estática',
                    checked: isChecked,
                    onChange: () => {
                        setChecked(!isChecked);
                        wp.data.dispatch('core/editor').editPost({meta: {_static_output_html: !isChecked}});
                    },
                }
            );
        }
    );
    
    const Output = () => {
        return el(PluginPostStatusInfo, {},
            el(MetaTextControl)
        );
    }

    
    wp.data.subscribe( function () {
        var newFormat = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
        var plugin_relation = getPlugin('relation-output');
        if(newFormat=='publish' && typeof plugin_relation=='undefined'){
            registerPlugin('relation-output', { render: Output });
        }else if(newFormat!='publish' && typeof plugin_relation!='undefined'){
			wp.data.dispatch('core/editor').editPost({meta: {_static_output_html: false}});
            unregisterPlugin('relation-output');
        }
      } 
    );

}) (
	window.wp.plugins,
	window.wp.editPost,
	window.wp.element,
	window.wp.components,
    window.wp.compose,
    window.wp.data,
);