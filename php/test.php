<?php

const CERTIFICADO = "cert.pem";
const PASSPHRASE = "e89j23qdm0r89q3las4u";
const MANTIS_URL = "https://190.111.249.189/mantis/api/soap/mantisconnect.php?wsdl";
const MANTIS_USER = "operador";
const MANTIS_PASSWORD = "operador3$";
const MANTIS_CATEGORIA = "Alarmas";

const DUE_DATE_ID = 24;
const DUE_DATE_NAME = "syn_due_date";
const DUE_TIME_ID = 25;
const DUE_TIME_NAME = "syn_due_time";

// Creo el cliente SOAP
try {
	$soapClient = new SoapClient(MANTIS_URL, array(
		"trace" => 1,
		"local_cert" => CERTIFICADO,
		"passphrase" => PASSPHRASE
		)
	);
} catch (Exception $e) {
	echo "No se puede crear el cliente Soap.";
	die();
}


try {
	$cft = $soapClient->mc_enum_custom_field_types(MANTIS_USER, MANTIS_PASSWORD);
} catch (Exception $e) {
	echo "No se pudo crear el ticket." . "\n" . $e->getMessage();
	die();
}
echo "<pre>";
print_r($cft);
echo "</pre>";
//die();
$data = 45;
try {
	$pj = $soapClient->mc_project_get_custom_fields(MANTIS_USER, MANTIS_PASSWORD, $data);
} catch (Exception $e) {
	echo "No se pudo crear el ticket." . "\n" . $e->getMessage();
	die();
}

print "<br><br>";

echo "<pre>";
print_r($pj);
echo "</pre>";
//die();



$id = "";
$summary = "Prueba de creacion de ticket";
//$dateHoy = date( 'Y-m-d', time() );
//$date = date_create('2014-09-17');
$fecha = strtotime("+1 day");
$hora = date("H:i");


$custom = array(

	array(
		'field' => array('id' => DUE_DATE_ID, 'name' => DUE_DATE_NAME),
		'value' => $fecha
	),

	array(
		'field' => array('id' => DUE_TIME_ID, 'name' => DUE_TIME_NAME),
		'value' => $hora
	)

);

//echo $custom;
//die();

// Armo un array con la info del ticket
$issueData = array(
	"project"	=> array('id' => 45),
	"category"	=> MANTIS_CATEGORIA,
	"summary"	=> $summary,
	"description" => utf8_encode($summary),
	"custom_fields" => $custom
);

// Creo el ticket y devuelvo su id 
try {
	$id = $soapClient->mc_issue_add(MANTIS_USER, MANTIS_PASSWORD, $issueData);
} catch (Exception $e) {
	echo "No se pudo crear el ticket." . "\n" . $e->getMessage();
	die();
}

echo $id;