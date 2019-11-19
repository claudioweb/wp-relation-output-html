<?php $user = wp_get_current_user(); ?> 
<div class="wrap">

	<?php if(!empty($_GET['loading_deploy'])): ?>
		<div style="background: #0ece14; color: #000; text-align: center; padding: 15px; font-size: 18px;">Sincronização efetuada com sucesso!</div>
	<?php endif; ?>

	<h2><?php echo $this->name_plugin; ?> - Configurações</h2>

	<form action="<?php echo admin_url(); ?>" method="POST" name="<?php echo sanitize_title($this->name_plugin); ?>">
		<table class="form-table">
			<tbody>

				<tr>
					<th>
						<label for="sincronizar_html">Sincronizar O HTML</label>
						<br>
						<small>Irá remover todos os posts/páginas e categorias para que seja gerado novamente!</small>
					</th>
					<td>
						<?php if(!in_array('administrator', $user->roles)){ echo '<small style="color:#f00;">Somente administradores podem gerar HTML!</small><br>'; } ?>
						<input <?php if(!in_array('administrator', $user->roles)){ echo 'disabled'; } ?> type="submit" onclick="if(confirm('Tem certeza que deseja sincronizar todas os posts e páginas HTML, isso pode demorar algumas horas.')){start_loading();}else{return false;}" name="deploy_all_static" id="deploy_all_static" class="button button-primary" value="SINCRONIZAR HTML">
					</td>
				</tr>
				
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
								<a style="position: absolute; margin-left: 650px; margin-top: 60px;" href="javascript:adicionar_api();">Adicionar nova API AJAX</a>
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
									<input name="<?php echo $key_field; ?>" type="checkbox" id="<?php echo $key_field; ?>" value="true" class="regular-text" <?php if(!empty(get_option($key_field))){echo'checked';} ?>> <small> Todas as imagens em: <br>
										(<b><?php echo wp_upload_dir()['baseurl']; ?></b>) serão TRANSFERIDAS</small>
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
					</tbody>
				</table>
			</form>
		</div>
		<div id="loading_deploy" style="display: none; top: 0; left: 0; z-index: 99999; position: fixed; width: 100%; height: 100%; background: rgba(255,255,255,0.95);">
			<img src="<?php echo plugin_dir_url(''); ?>relation-output-html/templates/loading.webp" alt="carregando" style="position: relative; margin: 7% auto; width: 300px; display: block; opacity:0.8;">
			<h1 style="text-align:center; top: 0; left: 0; width: 100%; margin: 30% auto; text-align: center; position: absolute;">Aguarde, estamos processando o HTML ...</h1>
		</div>
		<script>
			function start_loading(){
				jQuery(function(){
					jQuery("#loading_deploy").fadeIn();
				});
			}
			function adicionar_api(){
				jQuery(function(){

					var $tr = jQuery("#api_1_rlout").closest('tr');

        			// Now the one above it
        			// var $trAbove = $tr.prev('tr');

        			// Now insert the clone
        			$tr.clone().insertBefore($tr);
        		});
			}
		</script>