<?php
/**
 * Author: CRM 2go(R)
 */
class CRM2go_API {
	
	private static $apiUrl;
	private static $preloginUrl = 'https://app.crm2go.net/crm2go/prelogin';
	private static $apiTokenUrl = 'https://app.crm2go.net/';
	private $apiToken;
	
	function __construct($apiUrl, $apiToken) {
		$this->apiUrl = $apiUrl;
		$this->apiToken = $apiToken;
	}
	
	/*
	private function doApiRequest($module, $method, $params=array()) {
		if (!isset(self::$apiUrl)) {
			self::$apiUrl = get_option('c2g_wcc_zoho_host', 'https://crm.zoho.com').'/crm/private/xml/';
		}
		
		$params['authtoken'] = $this->authToken;
		$params['scope'] = 'crmapi';
		$requestUrl = CRM2go_API::$apiUrl.$module.'/'.$method;
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'content' => http_build_query($params),
				'header' => 'Content-Type: application/x-www-form-urlencoded'
			)
		));
		$result = file_get_contents($requestUrl, false, $context);
		if ($result === false)
			return false;
		$result = simplexml_load_string($result);
		if ($result === false)
			return false;
		return $result;
	} */
	
	private function fieldsToXml($module, $rows) {
		$xml = new SimpleXMLElement("<$module />");
		foreach ($rows as $i => $fields) {
			$row = $xml->addChild('row');
			$row->addAttribute('no', $i + 1);
			
			foreach ($fields as $fieldName => $fieldValue) {
				$field = $row->addChild('FL', str_replace('&', '&amp;', $fieldValue));
				$field->addAttribute('val', $fieldName);
			}
		}
		return $xml->asXML();
	}
	
	public static function getApiToken($email, $password) {
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'content' => http_build_query(array(
					//'SCOPE' => 'ZohoCRM/crmapi',
					'email' => $email,
					'json' => 1,
					//'DISPLAY_NAME' => 'WooCommerce - '.substr($_SERVER['HTTP_HOST'], 0, 25)
				)),
				'header' => 'Content-Type: application/x-www-form-urlencoded'
			)
		));
		$result = json_decode(file_get_contents(CRM2go_API::$preloginUrl, false, $context),true);
		
		if($result['slang']){
		//echo 'thers slang';
			$context2 = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'content' => http_build_query(array(
						'usuario' => $email,
						'app' => 'woocomerce_connector',
						'password' => $password,
						'validez' => 3650
					)),
					'header' => 'Content-Type: application/x-www-form-urlencoded'
				)
			));

			$result2 = json_decode(file_get_contents($result['apiUrl']. '/api/login', false, $context2),true);
			//var_dump($result2);
			if(!$result2['status']){
				return array('status' => false, 'message' => $result2['message'], 'slang' => $result['slang']);
			}
			else{
				return array('status' => true, 'apiUrl' => $result['apiUrl'], 'slang' => $result['slang'], 'token' => $result2['token'], 'usuario' => $result2['usuario']['usuario'], 'nombreCompleto' => $result2['usuario']['nombre']. ' ' .$result2['usuario']['apellido']);
			}

		}
		elseif($result['error']){
			return array('status' => false, 'message' => $result['error']);
		}
		elseif(count($result['instancias']) > 1){
			return array('status' => true, 'instancias' => $result['instancias']);
		}

		//return $result;
		/*
		if ($result === false)
			return false;
		foreach(explode("\n", $result) as $line) {
			$line = trim($line);
			if (strlen($line) > 10 && substr($line, 0, 10) == 'AUTHTOKEN=')
				return substr($line, 10);
		}
		return false;
		*/
	}
	
	public function addContact($contactData, $orderDetails = array(), $options) {
		$contactData['estadocomercial'] = 'Cliente';

		$context = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'content' => http_build_query(array(
						'token' => $this->apiToken,
						'entidad' => 'contacto',
						'accion' => 'buscar',
						'string' => $contactData['email1'],
					)),
					'header' => 'Content-Type: application/x-www-form-urlencoded'
				)
			));

		$result = json_decode(file_get_contents($this->apiUrl. '/contactos/entidad:contacto/accion:buscar', false, $context),true);
		if(count($result['data']) == 0){
			$contactData['token'] = $this->apiToken;
			$contactData['entidad'] = 'contacto';
			$contactData['accion'] = 'agregar';
			$contactData['id'] = 'n';

			$context2 = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'content' => http_build_query($contactData),
					'header' => 'Content-Type: application/x-www-form-urlencoded'
				)
			));

			$result2 = json_decode(file_get_contents($this->apiUrl. '/contactos/entidad:contacto/accion:agregar', false, $context2),true);
			if($result2['status']){
				$contactData['id'] = $result2['id'];
			}
		}

		elseif((($result['data']) >= 1) && ($options['updateExisting'])){
			$contactData['token'] = $this->apiToken;
			$contactData['entidad'] = 'contacto';
			$contactData['accion'] = 'editar';
			$contactData['id'] = $result['data'][0]['id'];

			$context2 = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'content' => http_build_query($contactData),
					'header' => 'Content-Type: application/x-www-form-urlencoded'
				)
			));

			$result2 = json_decode(file_get_contents($this->apiUrl. '/contactos/entidad:contacto/accion:editar', false, $context2),true);
		}
		if($result2['status']){
			$histData = array(
				'token' => $this->apiToken,
				'entidad' => 'historial',
				'accion' => 'agregar',
				'id' => $contactData['id'],
				'actividad' => 'Venta',
				'estado' => 'Completada',
				'referencia' => 'Nueva compra desde WooComerce' .((isset($orderDetails['order_number'])) ? ' - Orden Nro. ' .$orderDetails['order_number'] : ''),
				'notas' => '',
			);
			if($options['orderDetails']){
				foreach($orderDetails['items'] as $item){
					$histData['notas'] .= $item['producto']. ' x ' .$item['cantidad']. PHP_EOL;
				}
			}
			if($options['followUp']) $histData['force_notification'] = 1;

			$context3 = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'content' => http_build_query($histData),
					'header' => 'Content-Type: application/x-www-form-urlencoded'
				)
			));

			$result3 = json_decode(file_get_contents($this->apiUrl. '/contactos/entidad:historial/accion:agregar', false, $context3),true);
		
			
			


			/*if($options['followUp']){
				$histData2 = array(
					'token' => $this->apiToken,
					'entidad' => 'historial',
					'accion' => 'agregar',
					'id' => $contactData['id'],
					'actividad' => 'Llamado saliente',
					'estado' => 'Pendiente',
					'referencia' => 'Seguimiento: de compra desde WooComerce' .((isset($orderDetails['order_number'])) ? ' (Orden: ' .$orderDetails['order_number'].')' : ''),
					'notas' => '',
					'fechahora' => 'now',
				);
				if($options['orderDetails']){
					foreach($orderDetails['items'] as $item){
						$histData2['notas'] .= $item['producto']. ' x ' .$item['cantidad']. PHP_EOL;
					}
				}
	
				$context4 = stream_context_create(array(
					'http' => array(
						'method' => 'POST',
						'content' => http_build_query($histData2),
						'header' => 'Content-Type: application/x-www-form-urlencoded'
					)
				));

				$result4 = json_decode(file_get_contents($this->apiUrl. '/contactos/entidad:historial/accion:agregar', false, $context4),true);
			}*/



		}
		return $result2['status'];

	}
	
	public function addLead($leadData, $updateExisting=false) {
		$result = $this->doApiRequest('Leads', 'insertRecords', array('newFormat' => 1, 'duplicateCheck' => ($updateExisting ? 2 : 1), 'xmlData' => $this->fieldsToXml('Leads', array($leadData))));
		return !isset($result->error);
	}
}
?>