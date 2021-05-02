<div class="wrap">
	<h2>
		<?php echo $this->name_plugin; ?> - Documentação
		<br>
		<small>A documentação abaixo são escritas apenas para desenvolvedores, ou pessoas que possuem fácil acesso ao código do site ou servidor para que possa ter sucesso na implementação e estatização do seu projeto</small>
	</h2>
	
	<div role="tabpanel">
		<!-- Nav tabs -->
		<!--<ul class="nav nav-tabs" role="tablist">
			<li role="presentation" class="active">
				<a href="#output_html" aria-controls="output_html" role="tab" data-toggle="tab"><b>OUTPUT HTML</b></a>
			</li>
			<li role="presentation">
				<a href="#aws_s3" aria-controls="aws_s3" role="tab" data-toggle="tab"><b>AWS S3</b></a>
			</li>
			<li role="presentation">
				<a href="#ftp_server" aria-controls="ftp_server" role="tab" data-toggle="tab"><b>FTP SERVER</b></a>
			</li>
			<li role="presentation">
				<a href="#github_pages" aria-controls="github_pages" role="tab" data-toggle="tab"><b>GITHUB PAGES</b></a>
			</li>
		</ul>-->

		<!-- Tab panes -->
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="static_html">
				<div class="main_schema">
					<h3>Como funciona o Plugin RELATION OUTPUT HTML</h3>

					<ol>
						<li>
							Ao instalar e preencher os campos <b>post_types</b> e <b>taxonomies</b> em <a href="<?php echo site_url('wp-admin/admin.php?page=relation-output-html-config'); ?>">configurações</a> do plugin, automaticamente o plugin ja começa a gerar os arquivos na raiz do projeto, na pasta /html em <b><a target="_blank" href="<?php echo site_url('/html'); ?>"><?php echo site_url('/html'); ?></a></b>

							<br>
							<br>
							<b>Campos de configurações:</b>
							<ul>
								<li>
									<p>
										<b>Sincronizar O HTML</b>
										<br>
										Ao clicar neste botão automaticamente o plugin percorre todos os posts e categorias no site, conforme você indicou nos campos de post_types e taxonomies
									</p>
								</li>
								<li>
									<p>
										<b>Substituir a URL</b>
										<br>
										Insira a URL do novo local onde irá ser realizado o upload, ou deixe em branco para que seja gerado a URL automaticamente no mesmo ambiente do wordpress
										<br> exemplo: (url atual) -> http://localhost | (nova url) https://claudioweb.com.br
									</p>
								</li>
								<li>
									<p>
										<b>Post Type para deploy</b>
										<br>
										Selecione somente os post_types onde deseja que o plugin percorra para gerar o .html apartir do link gerado pela função <b>get_permalink()</b> do wordpress
									</p>
								</li>
								<li>
									<p>
										<b>Taxonomy para deploy</b>
										<br>
										Selecione somente as taxonomies onde deseja que o plugin percorra para gerar o .html apartir do link gerado pela função <b>get_term_link()</b> do wordpress
									</p>
								</li>
								<li>
									<p>
										<b>Transformar UPLOADS</b>
										<br>
										Preencha esse campo somente, se desejar que todas as imagens na pasta /uploads sejam copiadas novamente para a nova pasta /html/uploads
									</p>
								</li>
								<li>
									<p>
										<b>URL API AJAX STATIC</b>
										<br>
										Preencha neste campo um link de AJAX do wordpress, assim o plugin ira gerar um arquivo .JSON com a resposta deste arquivo. 
										<br>
										(recomendo trazer e trabalhar com a resposta de todos os registros do projeto, exemplo : posts_per_page=>-1)
									</p>
								</li>
								<li>
									<p>
										<b>Storage AWS S3</b>
										<br>
										Todos os grupos de campos, são obrigatórios para obter uma configuração de upload correta ao Storage AWS S3
									</p>
								</li>
								<li>
									<p>
										<b>FTP SERVER</b>
										<br>
										Todos os grupos de campos, são obrigatórios para obter uma configuração de upload correta ao FTP SERVER
									</p>
								</li>
								<li>
									<p>
										<b>GITHUB PAGES</b>
										<br>
										Para configurar corretamente o github pages ao seu projeto, você terá que criar um novo repositório e autenticar sua maquina via .ssh ao github na aba <b><a href="https://github.com/settings/keys" target="_blank">SSH and GPG keys</a></b>.
										<br>
										para mais informações sobre este serviço acesse: <a href="https://pages.github.com/" target="_blank">https://pages.github.com/</a>
									</p>
								</li>
							</ul>
						</li>
					</ol>
				</div>
			</div>

			<!--<div role="tabpanel" class="tab-pane active" id="static_html">
				<div class="main_schema">
					<h3>Como funciona o Plugin RELATION OUTPUT HTML</h3>

					<ol>
						<li>
							Ao instalar e preencher os campos <b>post_types</b> e <b>taxonomies</b> em <a href="<?php echo site_url('wp-admin/admin.php?page=relation-output-html-config'); ?>">configurações</a> do plugin, automaticamente o plugin ja começa a gerar os arquivos na raiz do projeto, na pasta /html em <b><a target="_blank" href="<?php echo site_url('/html'); ?>"><?php echo site_url('/html'); ?></a></b>

							<br>
							<br>
							<b>Campos de configurações:</b>
							<ul>
								<li>
									<p>
										<b>Sincronizar O HTML</b>
										<br>
										Ao clicar neste botão automaticamente o plugin percorre todos os posts e categorias no site, conforme você indicou nos campos de post_types e taxonomies
									</p>
								</li>
							</ul>
						</li>
					</ol>
				</div>
			</div>-->

		</div>
	</div>
</div>
<style>
	.main_schema {
		background: #000;
		color: #fff;
		padding: 30px 15px;
	}
	.main_schema h3 {
		margin-bottom: 30px;
	}
	.main_schema span {
		color: green;
	}
	.main_schema ul {
		list-style: circle;
		margin-left: 30px;
		margin-top: 15px;
	}

	.main_schema ol > li {
		margin-bottom: 60px;
	}
</style>
<!-- Latest compiled and minified CSS & JS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
<script src="//code.jquery.com/jquery.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>