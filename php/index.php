<?php

header('Access-Control-Allow-Origin: *');

require_once("xmlrpc.inc"); 
require_once("simple_html_dom.php");

/** CONSTANTES **/ 
const CERTIFICADO = "cert.pem";
const PASSPHRASE = "e89j23qdm0r89q3las4u";
const WIKI_HOST = "stss.synchro-technologies.com";
const WIKI_PORT = 443;
const WIKI_URL = "/SynWiki/?action=xmlrpc2";
const WIKI_MSG = "getPageHTML";
const MANTIS_URL = "https://190.111.249.189/mantis/api/soap/mantisconnect.php?wsdl";
const MANTIS_USER = "operador";
const MANTIS_PASSWORD = "operador3$";
const MANTIS_CATEGORIA = "Alarmas";
const MANTIS_DUE_DATE_ID = 24;
const MANTIS_DUE_DATE_NAME = "syn_due_date";
const MANTIS_DUE_TIME_ID = 25;
const MANTIS_DUE_TIME_NAME = "syn_due_time";
const DB_HOST = "190.111.249.189";
const DB_NAME_STDC = "stdc";
const DB_NAME_MANTIS = "bugtracker";
const DB_USER = "stdcreport";
const DB_PASSWORD = "r3p0rt_1947";

/** FUNCIONES DE LA WIKI **/
function get_wiki_pagina($cliente)
{
	if ($cliente === "nobleza") {
		$pagina = "NoblezaPiccardo";
	} else if ($cliente === "donmario") {
		$pagina = "DonMario";
	} else {
		$pagina = ucfirst($cliente);
	}
	return "Alarmas" . $pagina;
}

function get_li_tag($cliente, $alarma)
{
	$pagina = get_wiki_pagina($cliente);

	// Preparo el mensaje para obtener la pagina
	$msg = new xmlrpcmsg(WIKI_MSG, array(php_xmlrpc_encode($pagina)));
	
	// Creo el cliente xmlrpc
	$client = new xmlrpc_client(WIKI_URL, WIKI_HOST, WIKI_PORT);
	$client->setCertificate(CERTIFICADO, PASSPHRASE);
	$client->setDebug(0);

	// Obtengo la pagina
	$response = &$client->send($msg, 0, 'https');
	if(!$response->faultCode()) {
		$value = php_xmlrpc_decode($response->value());
	} else {
		print "Error: ";
		print $response->faultCode() . " Reason: " . $response->faultString();
		die();
	}

	// Creo el objeto html y lo cargo con la pagina de la wiki
	$html = new simple_html_dom();
	$html->load($value);
	
	// Parseo el html y guardo los tags pedidos
	$h3 = $html->find("h3");

	// Realizo un loop en los tags <h3> buscando el que concuerda con la alarma pedida.
	foreach ($h3 as $h)
	{
				
		if ($h->innertext === $alarma) {

			// Con enter despues de la alarma en la Wiki
			if ($h->next_sibling()->next_sibling()->next_sibling()->tag === "ul") {
				// Sin enter despues de la alarma en la Wiki
				#$li = $h->next_sibling()->next_sibling()->children();
				
				$li = $h->next_sibling()->next_sibling()->next_sibling()->children();
			}	

			break;
		}
	}

	// Devuelvo el array si existe info
	if (isset($li)) {
		return $li;
	} else {
		return false;
	}
}

function get_wiki_protocolo($cliente, $alarma)
{
	$li = get_li_tag($cliente, $alarma);

	if ($li === false) {
		return false;
	}

	// Agrrego al array cada elemento <li> como texto
	foreach($li as $l) {
		$salida[] = $l->plaintext;
	}

	// Devuelvo el array si existe info
	if (isset($salida)) {
		return $salida;
	} else {
		return false;
	}
}

function get_wiki_accion($cliente, $alarma, $estado)
{
	$li = get_li_tag($cliente, $alarma);

	if ($li === false) {
		return false;
	}

	// "Corto y pego" el campo de accion correspondiente para guardar solo el texto de la accion a realizar
	if ($estado === "warning") {
		$salida = explode(":", $li[2]->plaintext);
		array_shift($salida);
		$salida = implode("", $salida);
	} else {
		$salida = explode(":", $li[3]->plaintext);
		array_shift($salida);
		$salida = implode("", $salida);
	}

	// Devuelvo el array si existe info
	if (isset($salida)) {
		return $salida;
	} else {
		return false;
	}
}

/** FUNCIONES DE MANTIS **/
// Funcion que genera el conector a la base de datos
function get_db_connection($base)
{
	switch ($base) {
		case "stdc":
			$dbName = DB_NAME_STDC;
			break;
		case "mantis":
			$dbName = DB_NAME_MANTIS;
			break;
	}

	try {
	    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . $dbName . "", DB_USER, DB_PASSWORD);
	    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
	    echo "Could not connect to the database.";
	    die();
	}

	return $db;
}

// Funcion que devuelve el id de proyecto de un cliente
function get_stdc_project_id($cliente)
{
	$db = get_db_connection("stdc");

    try {
        $results = $db->prepare("SELECT pct_project_id FROM project WHERE pct_canopsis_tag = ? LIMIT 1");
        $results->bindValue(1, $cliente);
        $results->execute();
    } catch (Exception $e) {
        echo "pct_project_id FROM project could not be retrieved from the database." . "\n" . $e->getMessage();
        die();
    }

    $projectId = $results->fetch(PDO::FETCH_ASSOC);

    return $projectId['pct_project_id'];
}

// Funcion que busca un ticket y devuelve su id en caso de existir
function get_mantis_id($projectId, $summary)
{
	$db = get_db_connection("mantis");

	try {
        $results = $db->prepare("SELECT id FROM mantis_bug_table WHERE project_id = ? AND summary = ? AND status < 80");
        $results->bindValue(1, $projectId);
        $results->bindValue(2, $summary);
        $results->execute();
    } catch (Exception $e) {
        echo "id FROM mantis_bug_table could not be retrieved from the database.";
        die();
    }

    $id = $results->fetch(PDO::FETCH_ASSOC);

    return $id['id'];
}

// Funcion que crea un ticket
function create_mantis_ticket($cliente, $alarma, $host, $estado, $summary, $description)
{
	$projectId = get_stdc_project_id($cliente);

	// Buscar si existe creado ticket y lo devuelvo si es el caso
	$id = get_mantis_id($projectId, $summary);
	if (!empty($id)) {
		$salida = array(
			"existe" => true,
			"id" => $id
		);
		return $salida;
	}

	// Creo el cliente SOAP
	try {
		$soapClient = new SoapClient(MANTIS_URL, array(
			"trace" => 1,
			"local_cert" => CERTIFICADO,
			"passphrase" => PASSPHRASE
			)
		);
	} catch (Exception $e) {
		echo "No se puede crear el cliente Soap." . "\n" . $e->getMessage();
		die();
	}

	// Cargo en un array los campos "custom" del ticket (fecha y hora limite)
	$custom = array(
		array(
			'field' => array('id' => MANTIS_DUE_DATE_ID, 'name' => MANTIS_DUE_DATE_NAME),
			'value' => strtotime("+1 day")
		),
		array(
			'field' => array('id' => MANTIS_DUE_TIME_ID, 'name' => MANTIS_DUE_TIME_NAME),
			'value' => date("H:i")
		)
	);

	// Armo un array con la info del ticket
	$issueData = array(
		"project"	=> array('id' => $projectId),
		"category"	=> MANTIS_CATEGORIA,
		"summary"	=> $summary,
		"description" => utf8_encode($description),
		"custom_fields" => $custom
	);

	// Creo el ticket y devuelvo su id 
	try {
		$id = $soapClient->mc_issue_add(MANTIS_USER, MANTIS_PASSWORD, $issueData);
	} catch (Exception $e) {
		echo "No se pudo crear el ticket." . "\n" . $e->getMessage();
		die();
	}

	$salida = array(
		"existe" => false,
		"id" => $id
	);
	return $salida;
}

/** COMIENZO SCRIPT **/
// Integracion WIKI
if ($_SERVER['REQUEST_METHOD'] === "POST" and $_POST['integracion'] === 'wiki')
{
	if (isset($_POST['cliente']) and isset($_POST['host']) and isset($_POST['servicio']) and isset($_POST['estado'])) {

		// Guardo los datos ingresados
		$cliente = trim($_POST['cliente']);		
		$host = trim($_POST['host']);
		$servicio = trim($_POST['servicio']);
		$estado = trim($_POST['estado']);

		// Armo los datos compuestos
		$alarma = $host . "_" . $servicio;

		// Busco la accion a realizar a partir de los datos ingresados
		// Si existe la accion, la devuelvo codificada en utf8
		$accion = get_wiki_accion($cliente, $alarma, $estado);
		if ($accion === false) {
			echo "<h1>Error:</h1>" . "<em>No se encontro la accion a realizar.</em>";
		} else {
			$salida = "<b>Alarma:</b> " . $alarma . "<br>" . "<b>Servidor:</b> " . $host . "<br>" . "<b>Estado:</b> " . $estado . "<br>" . "<b>Accion:</b> " . $accion;
			echo utf8_encode($salida);		
		}
		
	} else {
		echo "<h1>Error:</h1>" . "<em>Datos informados incorrectos.</em>";
		echo "<br>" . $_POST['cliente'] . "<br>" . $_POST['host'] . "<br>" . $_POST['servicio'] . "<br>" . $_POST['estado'];
	}

}
// INTEGRACION MANTIS
else if ($_SERVER['REQUEST_METHOD'] === "POST" and $_POST['integracion'] === 'mantis')
{
	if (isset($_POST['cliente']) and isset($_POST['host']) and isset($_POST['servicio']) and isset($_POST['estado'])) {

		// Guardo los datos enviados
		$cliente = trim($_POST['cliente']);		
		$host = trim($_POST['host']);
		$servicio = trim($_POST['servicio']);
		$estado = trim($_POST['estado']);

		// Armo los datos compuestos
		$alarma = $host . "_" . $servicio;
		$summary = "@" . $cliente . ":" . $host . ":" . $alarma;

		// POR PRECAUCIION, chequeo si en el protocolo hay una accion a realizar
		$accion = get_wiki_accion($cliente, $alarma, $estado);
		if ($accion === false) {
			echo "<h1>Error:</h1>" . "<em>No se encontro la accion a realizar.</em>";
			exit();
		}

		// Armo la descripcion del ticket a partir del protocolo obtenido de la wiki
		$wikiProtocolo = get_wiki_protocolo($cliente, $alarma);
		$protocolo = implode("<br>", $wikiProtocolo);
		$description = "<b>Host:</b> " . $host . "<br>" . "<b>Alarma:</b> " . $alarma . "<br>" . "<b>Estado:</b> " . $estado . "<br>" . "<b>Protocolo:</b>" . "<br>" . $protocolo;

		// Si se pidio solo el cuerpo del ticket, lo devuelvo.
		// En caso contrario, creo el ticket a partir de los datos enviados y devuelvo el ID
		// ya sea en caso de que exista previamente como en caso de crearse uno nuevo
		if (isset($_POST['opt']) and $_POST['opt'] === 'si') {
			echo "<p>" . $description . "</p>";
		} else {
			$mantisId = create_mantis_ticket($cliente, $alarma, $host, $estado, $summary, $description);
			$linkTicket = "<a target='_blank' href='https://stss.synchro-technologies.com/mantis/view.php?id=" . $mantisId['id'] . "'>" . $mantisId['id'] . "</a>";

			if ($mantisId['existe'] === true) {			
				echo "<p>El ticket ya existe y es el " . $linkTicket . "</p>";				
			} else {
				echo "<p>Se creo un ticket nuevo " . $linkTicket . "</p>";
			}
		}		
	}
}
else
{
?>

<!DOCTYPE html>
<html>
<head>
	<title>Integracion Canopsis-Wiki-Mantis</title>
</head>
<body>

	<h1>Consultar Wiki</h1>
	<form method="post" action="index2.php">
		Cliente: <input type="text" name="cliente"><br>
		Servicio: <input type="text" name="servicio"><br>
		Host: <input type="text" name="host"><br>
		Estado: <input type="text" name="estado"><br>
		<input type="text" name="integracion" value="wiki" hidden>
		<input type="submit" value="Consultar">
	</form>

	<br>
	<hr>

	<h1>Crear Mantis</h1>
	<form method="post" action="index2.php">
		Cliente: <input type="text" name="cliente"><br>
		Servicio: <input type="text" name="servicio"><br>
		Host: <input type="text" name="host"><br>
		Estado: <input type="text" name="estado"><br>
		Solo ver cuerpo?
		<select name="opt">
			<option value="si" selected>Si</option>
			<option value="no">No</option>
		</select>
		<input type="text" name="integracion" value="mantis" hidden>
		<input type="submit" value="Crear">
	</form>

</body>
</html>

<?
}
