<?php
/***************************************************************************
Plugin Name:  Relation Output HTML
Plugin URI:   http://www.claudioweb.com.br/
Description:  Este plugin transforma todos os conteúdos salvos e relaxionados em páginas staticas HTML para servidores como FTP e S3
Version:      2.0
Author:       Claudio Rabelo (claudioweb)
Author URI:   http://www.claudioweb.com.br/
Text Domain:  relation-output-html
**************************************************************************/

require __DIR__.'/vendor/autoload.php';

$request = new WpRloutHtml\App();
?>