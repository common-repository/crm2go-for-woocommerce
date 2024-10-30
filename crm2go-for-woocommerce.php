<?php
/**
 * Plugin Name: CRM2go for WooCommerce
 * Description: Agrega automaticamente los nuevos clientes de Woocommerce a CRM 2go.
 * Version: 1.0
 * Author: CRM 2go
 * Author URI: https://www.crm2go.net/
 */

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'c2g_wcc_action_links');
function c2g_wcc_action_links($links) {
	array_unshift($links, '<a href="'.esc_url(get_admin_url(null, 'admin.php?page=c2g_wcc')).'">Settings</a>');
	return $links;
}
 

add_action('admin_menu', 'c2g_wcc_admin_menu');
function c2g_wcc_admin_menu() {
	add_submenu_page('woocommerce', 'CRM 2go (integración)', 'CRM 2go (integración)', 'manage_woocommerce', 'c2g_wcc', 'c2g_wcc_page');
}


function c2g_wcc_page() {




	
	// Print header
	echo('
		<div class="wrap">
			<h2>CRM 2go - Conector para WooCommerce</h2>
	');
	
	// Check for WooCommerce
	if (!class_exists('WooCommerce')) {
		echo('<div class="error"><p>Este plugin requiere WooCommerce instalado y activado.</p></div></div>');
		return;
	} else if (!function_exists('wc_get_order_types')) {
		echo('<div class="error"><p>Este plugin requiere WooCommerce 2.2 o superior. Por favor actualiza tu instalaci&oacute;n de WooCommerce.</p></div></div>');
		return;
	}
	
	// Handle CRM 2go account fields submission
	if (!empty($_POST['c2g_wcc_crm2go_email']) && !empty($_POST['c2g_wcc_crm2go_password']) && check_admin_referer('c2g_wcc_save_settings')) {
		if (!class_exists('CRM2go_API')){
			require_once(__DIR__.'/CRM2go_API.class.php');
		}
		$postEmail = sanitize_email($_POST['c2g_wcc_crm2go_email']);
		if (!filter_var($postEmail, FILTER_VALIDATE_EMAIL)) {
			$postEmail = '';
			echo('<div class="error"><p>La direcci&oacute;n de correo es inv&aacute;lida</p></div></div>');
			return;
		}
		$postPassword = $_POST['c2g_wcc_crm2go_password'];
		if(strlen($postPassword) > 100){
			$postPassword = substr($postPassword,0,100);
		}
		$auth= CRM2go_API::getApiToken($postEmail, $postPassword);
		if (!$auth['status']) {
			echo('<div class="error"><p>Ha ocurrido un error intentando conectar tu cuenta de CRM 2go: <br /><strong>' .$auth ['message']. '</strong></p></div>');
			if(preg_match('/se ha encontrado/i',$auth['message'])) {
				echo ('<div class="updated"><p><a href="https://www.crm2go.net/?utm_source=c2g_plugin_woocommerce&utm_medium=weblink&utm_campaign=registro&utm_term=registrate_para_usar&utm_content=">Reg&iacute;strate</a> para comenzar a construir tu base de CRM, &iexcl;es gratis!</p></div>');
			}
			if(preg_match('/clave/i',$auth['message'])) {
				echo ('<div class="updated"><p><a target="_blank" href="https://app.crm2go.net/' .$auth['slang']. '/reset-password/' .str_replace('=','',base64_encode($postEmail)). '">Recuperar tu contrase&ntilde;a</a></p></div>');
			}
		} elseif (($auth['status']) && (isset($auth['instancias']))) {
			echo('<div class="error"><p>Elige un sistema al que conectarte</p></div>');
		} else {
			update_option('c2g_wcc_crm2go_api_url', $auth['apiUrl']. '/api');
			update_option('c2g_wcc_crm2go_api_token', $auth['token']);
			update_option('c2g_wcc_crm2go_email', $postEmail);
			update_option('c2g_wcc_crm2go_slang', $auth['slang']);
			update_option('c2g_wcc_crm2go_usuario', $auth['usuario']);
			update_option('c2g_wcc_crm2go_nombreCompleto', $auth['nombreCompleto']);
			echo('<div class="updated"><p>Has conectado tu cuenta de CRM 2go correctamente.</p></div>');
		}
	} else {
		
		if (!empty($_POST['c2g_wcc_crm2go_disconnect']) && check_admin_referer('c2g_wcc_save_settings')) {
			delete_option('c2g_wcc_crm2go_api_url');
			delete_option('c2g_wcc_crm2go_api_token');
			delete_option('c2g_wcc_crm2go_usuario');
			delete_option('c2g_wcc_crm2go_nombreCompleto');
			delete_option('c2g_wcc_crm2go_email');
			delete_option('c2g_wcc_crm2go_slang');
		}
		
		if (get_option('c2g_wcc_crm2go_api_token', false) === false) {
			echo('<div class="error"><p>No has conectado tu cuenta de CRM 2go a&uacute;n.</p></div>');
		}
	}
	
	// Handle other settings submission
	if (!empty($_POST) && check_admin_referer('c2g_wcc_save_settings') && (get_option('c2g_wcc_crm2go_api_token', false) !== false)) {
		
	
	
		update_option('c2g_wcc_add_contacts', empty($_POST['c2g_wcc_add_contacts']) ? 0 : 1);
		update_option('c2g_wcc_update_contacts', empty($_POST['c2g_wcc_update_contacts']) ? 0 : 1);
		update_option('c2g_wcc_contacts_lead_source', empty($_POST['c2g_wcc_contacts_lead_source']) ? 0 : 1);
		update_option('c2g_wcc_add_order_details', empty($_POST['c2g_wcc_add_order_details']) ? 0 : 1);
		update_option('c2g_wcc_add_order_notify', empty($_POST['c2g_wcc_add_order_notify']) ? 0 : 1);
		echo('<div class="updated"><p>Se guardaron tus preferencias.</p></div>');
	}
	
	echo('<form action="" method="post" style="margin-bottom: 30px;">');
	wp_nonce_field('c2g_wcc_save_settings');
	echo('<div id="poststuff">
			<div id="post-body" class="columns-2">
				<div id="post-body-content" style="position: relative;">
					<form action="#hm_sbp_table" method="post">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label>Cuenta CRM 2go:</label>
				</th>
				<td>');
	if (get_option('c2g_wcc_crm2go_api_token', false) !== false){
		//echo('<div class="updated"><p>Ya has conectado tu cuenta de CRM 2go.</p></div>');
		echo('		<p style="margin-bottom: 10px;">Ya has conectado tu cuenta de CRM 2go. Para conectar una cuenta diferente desconecta la actual:<br /><strong>' .get_option('c2g_wcc_crm2go_nombreCompleto', ''). '</strong> - ' .get_option('c2g_wcc_crm2go_email', ''). '</p>
					<div style="margin-bottom: 10px;">
						<label><input type="checkbox" name="c2g_wcc_crm2go_disconnect" value="1" /> Desconectar la cuenta</label>
					</div>
		');
		}
	else{
	echo('			<div style="margin-bottom: 10px;">
						<label style="display: inline-block; width: 160px;">E-mail de CRM 2go:</label>
						<input type="email" name="c2g_wcc_crm2go_email" value="'.(!empty($_POST['c2g_wcc_crm2go_email']) ? $postEmail : esc_html(get_option('c2g_wcc_crm2go_email'))).'" />
					</div>
					<div>
						<label style="display: inline-block; width: 160px;">Clave de CRM 2go:</label>
						<input type="password" name="c2g_wcc_crm2go_password" />
						<p class="description">S&oacute;lo utilizaremos tu clave para establecer la conexi&oacute;n, no la guardaremos.</p>
					</div>');
		}
	echo('
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label>Contacts:</label>
				</th>
				<td>
					<div style="margin-bottom: 5px;">
						<label>
							<input type="checkbox" id="c2g_wcc_add_contacts" name="c2g_wcc_add_contacts"'.(get_option('c2g_wcc_add_contacts', 1) ? ' checked="checked"' : '').' />
							Agregar nuevos clientes de WooCommerce como contactos en CRM 2go
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="c2g_wcc_update_contacts" name="c2g_wcc_update_contacts"'.(get_option('c2g_wcc_update_contacts', 1) ? ' checked="checked"' : '').' />
							Si el contacto ya existe, actualizarlo
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="c2g_wcc_add_order_notify" name="c2g_wcc_add_order_notify"'.(get_option('c2g_wcc_add_order_notify', 1) ? ' checked="checked"' : '').' />
							Notificarme en la <a href="https://www.crm2go.net/mobile/?utm_source=c2g_plugin_woocommerce&utm_medium=weblink&utm_campaign=download_mobile&utm_term=notificarme_de_compras&utm_content=notificarme_de_compras">aplicaci&oacute;n m&oacute;vil de CRM 2go</a>
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="c2g_wcc_add_order_details" name="c2g_wcc_add_order_details"'.(get_option('c2g_wcc_add_order_details', 1) ? ' checked="checked"' : '').' />
							Agregar los detalles de la compra (cantidades y detalles)
						</label>
					</div>
					<div style="margin-bottom: 5px; margin-left: 20px;">
						<label>
							<input type="checkbox" id="c2g_wcc_contacts_lead_source" name="c2g_wcc_contacts_lead_source"'.(get_option('c2g_wcc_contacts_lead_source', 1) ? ' checked="checked"' : '').' />
							Definir la fuente del contacto como WooCommerce (sobrescribe el que exista)
						</label>
					</div>
				</td>
			</tr>
		</table>
		<button type="submit" class="button-primary">Guardar</button>
		</form>
		</div> <!-- /post-body-content -->
			
		<div id="postbox-container-1" class="postbox-container">
			<div id="side-sortables" class="meta-box-sortables">
			
				<div class="postbox">
					<h2><a href="https://www.crm2go.net/?utm_source=c2g_plugin_woocommerce&utm_medium=weblink&utm_campaign=woocommerce_plugin&utm_term=registrate_para_usar&utm_content=registrate_para_usar" target="_blank">Reg&iacute;strate en CRM 2go</a></h2>
					<div class="inside">
						<p><strong><a href="https://www.crm2go.net/?utm_source=c2g_plugin_woocommerce&utm_medium=weblink&utm_campaign=woocommerce_plugin&utm_term=registrate_para_usar&utm_content=registrate_para_usar" target="_blank">Reg&iacute;strate en CRM 2go</a> para aprovechar al m&aacute;ximo las oportunidades:</strong></p>
						<ul style="list-style-type: disc; padding-left: 1.5em;">
<li>Integra tu correo electr&oacute;nico de Google.</li>
<li>Conecta el formulario de contacto de Wordpress</li>
<li>Aprovecha los gr&aacute;ficos y reportes de tu base de datos.</li>
<li>Programa correos autom&aacute;ticos y otras actividades.</li>
						</ul>
						<p>
							<a href="https://www.crm2go.net/planes/?utm_source=c2g_plugin_woocommerce&utm_medium=weblink&utm_campaign=woocommerce_upsale&utm_term=&utm_content=sidebar" target="_blank">Todos los planes &gt;</a>
						</p>
					</div>
				</div>
				
			</div> <!-- /side-sortables-->
		</div><!-- /postbox-container-1 -->
		
		</div> <!-- /post-body -->
		<br class="clear" />
		</div> <!-- /poststuff -->
		<script>
			jQuery(\'#c2g_wcc_add_contacts\').change(function() {
				if (jQuery(this).is(\':checked\')) {
					jQuery(\'#c2g_wcc_update_contacts\').attr(\'disabled\', false);
				} else {
					jQuery(\'#c2g_wcc_update_contacts\').attr(\'checked\', false).attr(\'disabled\', true);
				}
			});
			jQuery(\'#c2g_wcc_add_contacts\').change();
			jQuery(\'#c2g_wcc_add_leads\').change(function() {
				if (jQuery(this).is(\':checked\')) {
					jQuery(\'#c2g_wcc_update_leads\').attr(\'disabled\', false);
				} else {
					jQuery(\'#c2g_wcc_update_leads\').attr(\'checked\', false).attr(\'disabled\', true);
				}
			});
			jQuery(\'#c2g_wcc_add_leads\').change();
		</script>
	');
	include(__DIR__.'/plugin-comments.php');
	include(__DIR__.'/plugin-credit.php');
	echo 'ja';
	echo('</div>'); // /wrap
}

add_action('woocommerce_checkout_update_order_meta', 'c2g_wcc_process_order');
function c2g_wcc_process_order($orderId) {
	global $woocommerce;
	$apiUrl = get_option('c2g_wcc_crm2go_api_url');
	$apiToken = get_option('c2g_wcc_crm2go_api_token');

	$order = $woocommerce->order_factory->get_order($orderId);
	
	if (empty($order))
		return;
	
	if (!class_exists('CRM2go_API'))
		require_once(__DIR__.'/CRM2go_API.class.php');
	$crm2go = new CRM2go_API($apiUrl, $apiToken);
	
	if (get_option('c2g_wcc_add_contacts', 1)) {

		$contactData = array(
			'nombre' => $order->get_billing_first_name(),
			'apellido' => $order->get_billing_last_name(),
			'empresa' => $order->get_billing_company(),
			'telefono1' => $order->get_billing_phone(),
			'email1' => $order->get_billing_email(),
			'direccion' => $order->get_billing_address_1().(empty($order->get_billing_address_2()) ? '' : ' '.$order->get_billing_address_2()),
			'ciudad' => $order->get_billing_city(),
			'provincia' => $order->get_billing_state(),
			'cp' => $order->get_billing_postcode(),
			'pais' => $order->get_billing_country(),
		);
		foreach($order->get_items() as $item){
			$items[] = array('cantidad' => $item->get_quantity(), 'tipo' => $item->get_type(), 'producto' => $item->get_name());
		}
		$orderDetails = array(
			'payment_method' => $order->get_payment_method(),
			'order_number' => $order->get_order_number(),
			'items' => (isset($items)) ? $items : [],
		);

		if (get_option('c2g_wcc_contacts_lead_source', 0))
			$contactData['fuente'] = 'WooCommerce';

		$options = array(
			'orderDetails' => !empty(get_option('c2g_wcc_add_order_details', 1)),
			'followUp' => !empty(get_option('c2g_wcc_add_order_notify', 1)),
			'updateExisting' => !empty(get_option('c2g_wcc_update_contacts', 0)),
		);

		$crm2go->addContact($contactData, $orderDetails, $options);
	}
	

	
}

?>