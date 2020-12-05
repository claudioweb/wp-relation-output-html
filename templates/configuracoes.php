<?php $user = wp_get_current_user(); ?> 
<div class="wrap">

	<?php if(!empty($_GET['loading_deploy'])): ?>
		<div style="background: #0ece14; color: #000; text-align: center; padding: 15px; font-size: 18px;">Sincronização efetuada com sucesso!</div>
	<?php endif; ?>

	<h2><?php echo $this->name_plugin; ?> - Configurações</h2>

	<form action="<?php echo admin_url(); ?>" method="POST" name="<?php echo sanitize_title($this->name_plugin); ?>">
		<table class="form-table">
			<tbody>
				
				<?php foreach ($fields as $key_field => $field) : ?>
					
					<?php if($field['type']=='label'): ?>
						<tr>
							<th scope="row" style="padding-bottom: 0;">
								<label for="<?php echo $key_field; ?>">
									<h3 style="margin: 0;"><?php echo $field['label']; ?></h3>
								</label>
							</th>
						</tr>
					<?php endif; ?>

					<?php if($field['type']=='text'): ?>
						<tr>
							<th scope="row">
								<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
							</th>
							<td>
								<input name="<?php echo $key_field; ?>" type="text" id="<?php echo $key_field; ?>" value="<?php echo get_option($key_field); ?>" class="regular-text">
							</td>
						</tr>
					<?php endif; ?>

					<?php if($field['type']=='repeater'): ?>

						<tr>
							<td>
								<a href="javascript:;" style="position: absolute; margin-left: 650px; margin-top: 60px;" class="adicionar_api_rlout">Adicionar campo</a>
							</td>
						</tr>

						<?php $values_r = explode(",", get_option($key_field)); ?>
						<?php if(empty($values_r)): ?>
							<tr>
								<th scope="row">
									<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
								</th>
								<td>
									<input name="<?php echo $key_field; ?>[]" type="text" id="<?php echo $key_field; ?>" value="<?php echo get_option($key_field); ?>" class="regular-text">
								</td>
							</tr>
							<?php else: ?>
								<?php foreach($values_r as $key_r => $value_r): ?>
									<tr>
										<th scope="row">
											<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
										</th>
										<td>
											<input name="<?php echo $key_field; ?>[]" type="text" id="<?php echo $key_field; ?>" value="<?php echo $value_r; ?>" class="regular-text">
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>

						<?php endif; ?>

						<?php if($field['type']=='checkbox'): ?>
							<tr>
								<th scope="row">
									<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
								</th>
								<td>
									<input name="<?php echo $key_field; ?>" type="checkbox" id="<?php echo $key_field; ?>" value="true" class="regular-text" <?php if(!empty(get_option($key_field))){echo'checked';} ?>> 
								</td>
							</tr>
						<?php endif; ?>

						<?php if($field['type']=='time'): ?>
							<tr>
								<th scope="row">
									<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
									<small>Horário do servidor: <span style="color: #f00;"><?php echo date('H:i'); ?></span></small>
								</th>
								<td>
									<input name="<?php echo $key_field; ?>" type="time" id="<?php echo $key_field; ?>" value="<?php echo get_option($key_field); ?>" class="regular-text">
								</td>
							</tr>
						<?php endif; ?>

						<?php if($field['type']=='select'): ?>
							<tr>
								<th scope="row">
									<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
								</th>
								<td>
									<select name="<?php echo $key_field; ?><?php if($field['multiple']){echo'[]';} ?>" id="<?php echo $key_field; ?>" <?php if($field['multiple']){echo'multiple';} ?> >
										<?php foreach($field['options'] as $option): ?>
											<option value="<?php echo $option; ?>" <?php if(in_array($option, explode(',', get_option($key_field)))){echo'selected';} ?> ><?php echo $option; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endif; ?>

					<?php endforeach; ?>

					<tr>
						<td>
							<p class="submit">
								<input type="submit" name="salvar" id="salvar" class="button button-primary" value="Salvar alterações">
							</p>
						</td>
					</tr>

					<tr>
						<th>
							<label for="sincronizar_html">Sincronizar O HTML</label>
							<br>
							<small>Irá remover todos os posts/páginas e categorias para que seja gerado novamente!</small>
						</th>
						<td>
						
							<div class="form-group" style="margin-bottom: 16px;">
								<label for="">Post type</label>
								<select id="post_type_static" class="form-control">
									<option value="all">Todos</option>
									<?php foreach(get_post_types() as $pt): ?>
										<option value="<?php echo $pt; ?>"><?php echo $pt; ?></option>
									<?php endforeach ?>
								</select>
							</div>

							<div class="form-group" style="margin-bottom: 16px;">
								<label for="">Taxonomy</label>
								<select id="taxonomy_static" class="form-control">
									<option value="all" selected>Todos</option>
									<?php foreach(get_taxonomies() as $tax): ?>
										<option value="<?php echo $tax; ?>"><?php echo $tax; ?></option>
									<?php endforeach ?>
								</select>
							</div>
				
							<div class="form-group">
								<?php if(!in_array('administrator', $user->roles)){ echo '<small style="color:#f00;">Somente administradores podem gerar HTML!</small><br>'; } ?>
								<input <?php if(!in_array('administrator', $user->roles)){ echo 'disabled'; } ?> type="button" onclick="if(confirm('Tem certeza que deseja sincronizar todas os posts e páginas HTML, isso pode demorar algumas horas.')){start_static();}else{return false;}" name="deploy_all_static" id="deploy_all_static" class="button button-primary" value="SINCRONIZAR HTML">						
							</div>
						</td>
						
					</tr>
					<tr id="loading_static" style="display: none;">
						<td colspan="2">
							<h3 style="margin-left: 40px;">Processamento de HTML e JSON</h3>
							<span style="margin: 7px 0;display: block;">Total de (<b class="statics_page">0</b>) > (<b class="total_page">0</b>)</span>
							<img src="<?php echo plugin_dir_url(''); ?>wp-relation-output-html/templates/loading.webp" style="position: absolute;width: 30px;margin-top: -70px;" />
							<div id="results_static" style="padding: 15px; border: 1px #ddd solid; height: 200px; max-width: 800px; background: #fff; overflow-x:auto; overflow-y: scroll;"></div>
						</td>
					</tr>

					
				</tbody>
			</table>
		</form>
	</div>
	<!-- <div id="loading_deploy" style="display: none; top: 0; left: 0; z-index: 99999; position: fixed; width: 100%; height: 100%; background: rgba(255,255,255,0.95);">
		<img src="<?php echo plugin_dir_url(''); ?>wp-relation-output-html/templates/loading.webp" alt="carregando" style="position: relative; margin: 7% auto; width: 300px; display: block; opacity:0.8;">
		<h1 style="text-align:center; top: 0; left: 0; width: 100%; margin: 30% auto; text-align: center; position: absolute;">Aguarde, estamos processando o HTML ...</h1>
	</div> -->
	<script>
		function start_static(){
			jQuery(function(){
				jQuery("#loading_static").fadeIn();
				jQuery("#post_type_static").attr('disabled','disabled');
				jQuery("#taxonomy_static").attr('disabled','disabled');
				jQuery("#deploy_all_static").attr('disabled','disabled');
				get_urls();
			});
		}

		function get_urls(){

			jQuery('.total_page').html('0');

			var settings_json = {
				"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_deploy_json",
				"method": "GET",
				"timeout": 0,
				};

			jQuery.ajax(settings_json).done(function (response_json) {

				jQuery('.total_page').html(response_json.length+parseInt(jQuery('.total_page').html()));
				
				for (let index_j = 0; index_j < response_json.length; index_j++) {
					var current = parseInt(jQuery('.statics_page').html());
					jQuery('.statics_page').html(current+1);
					jQuery('#results_static').append('<p><a href="'+response_json[index_j]+'" target="_blank">'+response_json[index_j]+'</a></p>');
				}

				var post_type = jQuery('select#post_type_static').val();
				var taxonomy = jQuery('select#taxonomy_static').val();
				var settings = {
				"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_files&post_type="+post_type+"&taxonomy="+taxonomy,
				"method": "GET",
				"timeout": 0,
				};

				jQuery.ajax(settings).done(function (response) {
					var qtd = response.length;
					jQuery('.total_page').html(qtd+parseInt(jQuery('.total_page').html()));
					deploy(0, response);
				});
			}).fail(function (response_json){
				jQuery('#results_static').append('<p><a href="'+response_json+'" target="_blank">FAIL JSON</a></p>');
				setTimeout(function(){
					get_urls();
				},1000);
			});

		}
		
		function deploy(key_main, response){
			var settings_url = {
			"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_deploy&file_url="+response[key_main],
			"method": "GET",
			"timeout": 0,
			};

			jQuery.ajax(settings_url).done(function (response_url) {
				var total = parseInt(jQuery('.statics_page').html());
				var url_main = response_url.replace("<?php echo site_url(); ?>","<?php echo get_option('replace_url_rlout'); ?>");
				jQuery('.statics_page').html(total+1);
				jQuery('#results_static').append('<p><a href="'+url_main+'" target="_blank">'+url_main+'</a> - OK</p>');
				
				if(jQuery('.statics_page').html()==jQuery('.total_page').html()){
					jQuery('#loading_static img').hide();
					jQuery("#post_type_static").removeAttr('disabled');
					jQuery("#taxonomy_static").removeAttr('disabled');
					jQuery("#deploy_all_static").removeAttr('disabled');
				}else{
					deploy(key_main+1, response);
				}
			}).fail(function(response_url){
				var total = parseInt(jQuery('.statics_page').html());
				var url_main = response_url.replace("<?php echo site_url(); ?>","<?php echo get_option('replace_url_rlout'); ?>");
				jQuery('.statics_page').html(total+1);
				jQuery('#results_static').append('<p><a href="'+url_main+'" target="_blank">'+url_main+'</a> - FAIL </p>');
				
				if(jQuery('.statics_page').html()==jQuery('.total_page').html()){
					jQuery('#loading_static img').hide();
					jQuery("#post_type_static").removeAttr('disabled');
					jQuery("#taxonomy_static").removeAttr('disabled');
					jQuery("#deploy_all_static").removeAttr('disabled');
				}else{
					deploy(key_main+1, response[key_main]);
				}
			});
		}

		function start_loading(){
			jQuery(function(){
				jQuery("#loading_deploy").fadeIn();
			});
		}

		jQuery(function(){

			jQuery(".adicionar_api_rlout").click(function(){

				var $tr = jQuery(this).closest('tr').next();

        			// Now the one above it
        			// var $trAbove = $tr.prev('tr');

        			// Now insert the clone
        			$tr.clone().insertBefore($tr);
        		});
		});
	</script>