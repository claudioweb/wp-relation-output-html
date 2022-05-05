<?php 

use WpRloutHtml\Helpers;

$user = wp_get_current_user(); 

?> 
<div class="wrap">

	<?php if(!empty($_GET['loading_deploy'])): ?>
		<div style="background: #0ece14; color: #000; text-align: center; padding: 15px; font-size: 18px;">Sincronização efetuada com sucesso!</div>
	<?php endif; ?>

	<h2><?php echo $this->name_plugin; ?> - Configurações</h2>

	<form action="<?php echo admin_url(); ?>" method="POST" name="<?php echo sanitize_title($this->name_plugin); ?>">
		<table class="form-table">
			<tbody>
				<input type="hidden" name="keys_fields" value="<?php echo implode(',',array_keys($fields)); ?>">
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
								<input name="<?php echo $key_field; ?>" <?php if(!empty($field['disabled'])){echo 'disabled="'.$field['disabled'].'"';} ?> type="text" id="<?php echo $key_field; ?>" value="<?php if(!empty(Helpers::getOption($key_field))){echo Helpers::getOption($key_field);}else{echo $field['default'];} ?>" class="regular-text">
							</td>
						</tr>
					<?php endif; ?>

					<?php if($field['type']=='number'): ?>
						<tr>
							<th scope="row">
								<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
							</th>
							<td>
								<input name="<?php echo $key_field; ?>" <?php if(!empty($field['disabled'])){echo 'disabled="'.$field['disabled'].'"';} ?> type="number" id="<?php echo $key_field; ?>" value="<?php if(!empty(Helpers::getOption($key_field))){echo Helpers::getOption($key_field);}else{echo $field['default'];} ?>" class="regular-text">
							</td>
						</tr>
					<?php endif; ?>

					<?php if($field['type']=='repeater'): ?>

						<tr>
							<td>
								<a href="javascript:;" style="position: absolute; margin-left: 760px; margin-top: 60px;" class="adicionar_api_rlout">Adicionar campo</a>
							</td>
						</tr>

						<?php $values_r = explode(",", Helpers::getOption($key_field)); ?>

						<?php if(empty($values_r)): ?>
							<tr>
								<th scope="row">
									<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
								</th>
								<td>
									<input name="<?php echo $key_field; ?>[]" type="text" id="<?php echo $key_field; ?>" value="<?php echo Helpers::getOption($key_field); ?>" class="regular-text">
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
									<input name="<?php echo $key_field; ?>" type="checkbox" id="<?php echo $key_field; ?>" value="true" class="regular-text" <?php if(!empty(Helpers::getOption($key_field))){echo'checked';} ?>> 
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
									<input name="<?php echo $key_field; ?>" type="time" id="<?php echo $key_field; ?>" value="<?php echo Helpers::getOption($key_field); ?>" class="regular-text">
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
											<option value="<?php echo $option; ?>" <?php if(in_array($option, explode(',', Helpers::getOption($key_field)))){echo'selected';} ?> ><?php echo $option; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endif; ?>

						<?php if($field['type']=='select2'): ?>
							<tr class="select2-api-rlout">
								<th scope="row">
									<label for="<?php echo $key_field; ?>"><?php echo $field['label']; ?></label>
								</th>
								<td>
									<select style="width:350px;" data-action_ajax="<?php echo $field['action_ajax']; ?>" name="<?php echo $key_field; ?><?php if($field['multiple']){echo'[]';} ?>" id="<?php echo $key_field; ?>" <?php if($field['multiple']){echo'multiple';} ?> >
										<?php if(empty($field['options'])): ?>
											<?php $values_select = explode(',', Helpers::getOption($key_field)); ?>
											<?php foreach($values_select as $value_select2): ?>
												<?php
													$title = $value_select2;
													if(!empty($title)):
													$post = get_post(url_to_postid($value_select2)); 
													if(!empty($post->ID)){
														$title = $post->post_title;
													}else{

														$url_term = explode('/',str_replace(site_url(),'',$value_select2));
														if(empty(end($url_term))){
															unset($url_term[count($url_term)-1]);
														}
														$taxonomies = explode(',', Helpers::getOption('taxonomies_rlout'));
														foreach($taxonomies as $tax){
															$term = get_term_by('slug', end($url_term), $tax);
															if(!empty($term->term_id)){
																$title = $term->name;
															}
														}
													}
													
												?>
													<option value="<?php echo $value_select2; ?>" selected><?php echo $title; ?></option>
												<?php endif; ?>
											<?php endforeach; ?>
										<?php else: ?>
											<?php foreach($field['options'] as $option): ?>
												<option value="<?php echo $option; ?>" <?php if(in_array($option, explode(',', Helpers::getOption($key_field)))){echo'selected';} ?> ><?php echo $option; ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</td>
							</tr>
						<?php endif; ?>

					<?php endforeach; ?>

					<tr>
						<td>
							<p class="submit">
								<input type="submit" name="salvar_rlout" id="salvar" class="button button-primary" value="Salvar alterações">
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="sincronizar_html_erro">Arquivos com erro de upload</label>
							<br>
							<small>Todos os erros ao realizar UPLOAD</small>
						</th>
						<td>
							<?php $logs = $this->logs->list(); ?>
							<?php if(!empty($logs)): ?>
								<div class="error_log_upload" style="background: #fff;">
									<?php foreach($logs as $log): ?>
										<p style="padding:0 7px;"> - <?php echo date('d/m/Y H:i:s', strtotime($log->date_time)); ?> | <?php echo $log->file_static; ?></p>
									<?php endforeach; ?>
								</div>
								<br>
								<a href="javascript:;" onclick="if(confirm('Tem certeza que deseja excluir todos os logs de erro?')){truncate_logs();}else{return false;}" style="float: right;">Limpar Logs</a>
							<?php else: ?>
								<div class="error_log_upload" style="background: #fff;">
									<p style="padding: 7px;">Nenhum erro encontrado!</p>
								</div>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>
							<label for="sincronizar_html">Sincronizar O HTML</label>
							<br>
							<small>Irá gerar todos os posts/páginas e categorias!</small>
						</th>
						<td style="width: 500px;">
						
							<div class="form-group" style="margin-bottom: 16px;">
								<label for="">Estatizar JSONs (archive, taxonomy, terms)</label>
								<input type="checkbox" name="json_static" value="1" />
							</div>
							<div class="form-group" style="margin-bottom: 16px;">
								<label for="">Post type</label>
								<select id="post_type_static" class="form-control">
									<option value="" selected>Nenhum</option>
									<option value="all">Todos</option>
									<?php foreach(get_post_types() as $pt): ?>
										<option value="<?php echo $pt; ?>"><?php echo $pt; ?></option>
									<?php endforeach ?>
								</select>
							</div>

							<div class="form-group" style="margin-bottom: 16px;">
								<label for="">Taxonomy</label>
								<select id="taxonomy_static" class="form-control">
									<option value="" selected>Nenhum</option>
									<option value="all" >Todos</option>
									<?php foreach(get_taxonomies() as $tax): ?>
										<option value="<?php echo $tax; ?>"><?php echo $tax; ?></option>
									<?php endforeach ?>
								</select>
							</div>

							<div class="form-group">
								<?php if(!in_array('administrator', $user->roles)){ echo '<small style="color:#f00;">Somente administradores podem gerar HTML!</small><br>'; } ?>
								<?php $aux = $this->aux->list(); ?>
								<?php if(!empty($aux)): ?>			
									<input <?php if(!in_array('administrator', $user->roles)){ echo 'disabled'; } ?> type="button" onclick="if(confirm('Tem certeza que deseja que deseja continuar a sincronização de onde parou?')){start_static('continue');}else{return false;}" name="deploy_all_static_c" id="deploy_all_static_c" class="button button-primary" value="CONTINUAR ESTAIZAÇÃO">
								<?php endif; ?>			
								<input <?php if(!in_array('administrator', $user->roles)){ echo 'disabled'; } ?> type="button" onclick="if(confirm('Tem certeza que deseja sincronizar todas os posts e páginas HTML, isso pode demorar algumas horas.')){start_static();}else{return false;}" name="deploy_all_static" id="deploy_all_static" class="button button-primary" value="SINCRONIZAR TODO O HTML">						
							</div>
						</td>

						<td class="select2-api-rlout">
							<div class="form-group" style="margin-bottom: 16px;">
								<div class="form-group" style="margin-bottom: 16px;">
									<label for=""><b>Arquivos e diretórios Gerados em ./html</b></label>
									<br>
									<br>
									<select id="static_files_html" multiple class="form-control"  data-action_ajax name="gerated[]" style="width:350px;">
										<?php $base_html = Helpers::getOption('path_rlout').'/'; ?>
        								<?php $verify_files = scandir($base_html); ?>
										<?php unset($verify_files[0]); ?>
        								<?php unset($verify_files[1]); ?>
										<?php foreach($verify_files as $file): ?>
											<option value="<?php echo $base_html.$file; ?>">/<?php echo $file; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="form-group">
									<?php if(!in_array('administrator', $user->roles)){ echo '<small style="color:#f00;">Somente administradores podem fazer upload!</small><br>'; } ?>
									<input <?php if(!in_array('administrator', $user->roles)){ echo 'disabled'; } ?> type="button" onclick="if(confirm('Tem certeza que deseja realizar o upload ? isso pode levar alguns minutos.')){start_static_upload();}else{return false;}" name="deploy_all_static_upload" id="deploy_all_static_upload" class="button button-primary" value="REALIZAR UPLOAD">						
									<input <?php if(!in_array('administrator', $user->roles)){ echo 'disabled'; } ?> type="button" onclick="if(confirm('Tem certeza que deseja realizar o upload de tudo ?, isso pode levar alguns minutos.')){start_static_upload_all();}else{return false;}" name="deploy_all_static_upload_all" id="deploy_all_static_upload_all" class="button button-primary" value="UPLOAD DE TUDO">						
								</div>
							</div>
						</td>
						
					</tr>
					<tr id="loading_static" style="display: none;">
						<td colspan="2">
							<h3 style="margin-left: 40px;">Processamento de HTML e JSON</h3>
							<span style="margin: 7px 0;display: block;">Total de (<b class="statics_page">0</b>) > (<b class="total_page">0</b>)</span>
							<img src="<?php echo plugin_dir_url(''); ?>wp-relation-output-html/resources/loading.webp" style="position: absolute;width: 30px;margin-top: -70px;" />
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
		function start_static(status=null){
			jQuery(function(){
				jQuery("#loading_static").fadeIn();
				jQuery("#post_type_static").attr('disabled','disabled');
				jQuery("#taxonomy_static").attr('disabled','disabled');
				jQuery("#deploy_all_static").attr('disabled','disabled');
				jQuery("#deploy_all_static_c").attr('disabled','disabled');
				
				if(jQuery('input[name="json_static"]:checked').val()=="1"){
					get_urls(status);
				}else{
					set_deploy(status);
				}
			});
		}

		function get_urls(status){

			jQuery('.total_page').html('0');
			jQuery('.statics_page').html('0');

			var settings_json = {
					"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_deploy_json",
					"method": "GET",
					"timeout": 0,
				};

			jQuery.ajax(settings_json).done(function (response_json) {

				jQuery('.total_page').html(response_json.length+parseInt(jQuery('.total_page').html()));
				
				deploy_json(0, response_json, status);

			}).fail(function (response_json){
				jQuery('#results_static').append('<p><a href="'+response_json+'" target="_blank">FAIL JSON</a></p>');
				setTimeout(function(){
					get_urls(status);
				},1000);
			});

		}
		
		function deploy_json(key_main, response, status){
			var settings_url = {
			"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_deploy_curl_json&file="+response[key_main],
			"method": "GET",
			"timeout": 0,
			};

			jQuery.ajax(settings_url).done(function (response_url) {
				var total = parseInt(jQuery('.statics_page').html());
				var url_main = response_url.replace("<?php echo site_url(); ?>","<?php echo Helpers::getOption('replace_url_rlout'); ?>");
				jQuery('.statics_page').html(total+1);
				jQuery('#results_static').append('<p><a href="'+url_main+'" target="_blank">'+url_main+'</a> - OK</p>');
				
				
			}).fail(function(){
				var total = parseInt(jQuery('.statics_page').html());
				var url_main = response[key_main].replace("<?php echo site_url(); ?>","<?php echo Helpers::getOption('replace_url_rlout'); ?>");
				jQuery('.statics_page').html(total+1);
				jQuery('#results_static').append('<p><a href="'+url_main+'" target="_blank">'+url_main+'</a> - FAIL </p>');
				
			}).always(function(response_url){
				if(jQuery('.statics_page').html()==jQuery('.total_page').html()){

					set_deploy(status);
				}else{
					deploy_json(key_main+1, response, status);
				}
			});
		}
		
		function set_deploy(status){

			var post_type = jQuery('select#post_type_static').val();
			var taxonomy = jQuery('select#taxonomy_static').val();
			var settings = {
				"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_files&status="+status+"&post_type="+post_type+"&taxonomy="+taxonomy,
				"method": "GET",
				"timeout": 0,
			};

			jQuery.ajax(settings).done(function (response) {
				var qtd = response.length;
				jQuery('.total_page').html(qtd+parseInt(jQuery('.total_page').html()));

				window.localStorage.setItem('charge_static', 0);
				window.localStorage.setItem('statics_page', 0);

				deploy(0, response);
			});
		}

		function deploy(key_main, response){
			
			var numbers_requisition = parseInt(jQuery('input[name="range_posts_get_rlout"]').val());
			
			for (let index = 0; index < numbers_requisition; index++) {
				
				var new_key_main = key_main+index;
				
				if(response[new_key_main]){

					var settings_url = {
					"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_deploy&file_url="+response[new_key_main],
					"method": "GET",
					"timeout": 0,
					};

					jQuery.ajax(settings_url).done(function (response_url) {
						var url_main = response_url.replace("<?php echo site_url(); ?>","<?php echo Helpers::getOption('replace_url_rlout'); ?>");
						jQuery('#results_static').append('<p><a href="'+url_main+'" target="_blank">'+url_main+'</a> - OK</p>');

					}).fail(function(){
						var url_main = response[key_main].replace("<?php echo site_url(); ?>","<?php echo Helpers::getOption('replace_url_rlout'); ?>");
						jQuery('#results_static').append('<p><a href="'+url_main+'" target="_blank">'+url_main+'</a> - FAIL </p>');
						
					}).always(function(){

						var statics_page = parseInt(window.localStorage.getItem('statics_page'))+1;
						window.localStorage.setItem('statics_page', statics_page);

						var total = parseInt(jQuery('.statics_page').html());
						jQuery('.statics_page').html(total+1);


						charge = parseInt(window.localStorage.getItem('charge_static'))+1;
						window.localStorage.setItem('charge_static', charge);

						var finished = false;
						if(jQuery('.statics_page').html()==jQuery('.total_page').html()){
							finished = true;
						}

						if(index+1==numbers_requisition || finished==true){
							recursive_charge(new_key_main, response, charge, finished);
						}
					});
				}
			}
		}

		function recursive_charge(new_key_main, response, charge, finished=false){

			var limit_charge = 100;

			if(charge==limit_charge || finished==true){
				if(finished==true){
					jQuery('#loading_static img').hide();
					jQuery("#post_type_static").removeAttr('disabled');
					jQuery("#taxonomy_static").removeAttr('disabled');
					jQuery("#deploy_all_static").removeAttr('disabled');
				}
				upload_all(new_key_main, response);
			}else{
				var statics_page = parseInt(window.localStorage.getItem('statics_page'));
				var key_main_see = new_key_main+1;
				
				if(key_main_see==statics_page){
					deploy(key_main_see, response);
				}else{
					setTimeout(() => {
						charge = parseInt(window.localStorage.getItem('charge_static'));
						recursive_charge(new_key_main, response, charge);
					}, 500);
				}
			}
		}

		function upload_all(key_main, response_all){
			var settings = {
				"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_upload",
				"method": "GET",
				"timeout": 0,
			};

			jQuery('#results_static').append('<p>Aguarde, estamos fazendo uploads disponiveis...</p>');

			jQuery.ajax(settings).done(function (response) {
				
				if(response=='true'){
					window.localStorage.setItem('charge_static', 0);
					jQuery('#results_static').append('<p>- Upload completo realizado!</p>');
					if(response_all[key_main+1]){
						deploy(key_main+1, response_all);
					}
				}else{
					jQuery('#results_static').append('<p>'+response+'</p>');
					upload_all(key_main, response_all);
				}
			}).fail(function(){
				jQuery('#results_static').append('<p>Erro, estamos tentando fazer uploads novamente...</p>');
				upload_all(key_main, response_all);
			});
		}

		function truncate_logs(){
			var settings = {
				"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_truncate_log",
				"method": "GET",
				"timeout": 0,
			};

			jQuery.ajax(settings).done(function (response) {
				window.location.reload();
			});
		}

		function start_static_upload(){

			jQuery("#loading_static").fadeIn();
			jQuery('#loading_static span').hide();

			var form = new FormData();
			form.append("files", jQuery("#static_files_html").val());

			var settings = {
				"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_upload_specific",
				"method": "POST",
				"timeout": 0,
				"processData": false,
				"mimeType": "multipart/form-data",
				"contentType": false,
				"data": form
			};

			jQuery('#results_static').append('<p>Aguarde, estamos fazendo uploads disponiveis...</p>');

			jQuery.ajax(settings).done(function (response) {
				
				if(response){
					jQuery('#loading_static img').hide();
					jQuery('#results_static').append('<p>'+response+'</p>');
				}
			});
		}

		function start_static_upload_all(offset=0){

			jQuery("#loading_static").fadeIn();

			jQuery('.total_page').html(jQuery("#static_files_html option").length);

			var statics = [];
			jQuery("#static_files_html option").each(function(){
				statics.push(jQuery(this).val());
			});

			var statics_send = [];
			for (let index = offset; index < statics.length; index++) {

				statics_send.push(statics[index]);
				
				jQuery('.statics_page').html(offset+statics_send.length);
				var uploads_end = parseInt(jQuery('.statics_page').html());

				if(statics_send.length==100 || uploads_end==statics.length){
						
					var form = new FormData();
					form.append("files", statics_send);

					var settings = {
						"url": "<?php echo site_url(); ?>/wp-admin/admin-ajax.php?action=static_output_upload_specific",
						"method": "POST",
						"timeout": 0,
						"processData": false,
						"mimeType": "multipart/form-data",
						"contentType": false,
						"data": form
					};

					jQuery('#results_static').append('<p>Aguarde, estamos fazendo uploads disponiveis...</p>');

					jQuery.ajax(settings).done(function(response){
						if(response){
							jQuery('#loading_static img').hide();
							jQuery('#results_static').append('<p>'+response+'</p>');
							start_static_upload_all(offset+statics_send.length);
						}
					});

					return;
				}
			}
		}

		function start_loading(){
			jQuery(function(){
				jQuery("#loading_deploy").fadeIn();
			});
		}

		jQuery(function(){

			jQuery(".adicionar_api_rlout").click(function(){

				var $tr = jQuery(this).closest('tr').next();

        		$tr.clone().insertBefore($tr).find('input').attr('value','');
        	});
		});
	</script>