#!/usr/bin/php

<?php

function array2xml($array)
{
	$xml = '';
	foreach ($array as $index => $value) {
		$xml .= "<$index>";
		if (is_array($value)) {
			$xml .= "\n";
			$xml .= array2xml($value);
		} else {
			$xml .= "$value";
		}
		$xml .= "</$index>\n";
	}
	return $xml;
}

function xml2array($xml)
{
	$xmla = array();
	$xml = new SimpleXMLElement($xml);
	if ($xml->getName()) {
		$xmla[$xml->getName()] = array();
		foreach($xml->children() as $child) {
			$xmla[$xml->getName()][$child->getName()] = (string) $child;	
		}
	}
	
	return $xmla;
}

function post_xml($url, $xml) 
{
	$rs = curl_init();

	curl_setopt($rs, CURLOPT_URL, $url);	
	curl_setopt ($rs, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
	curl_setopt($rs, CURLOPT_POST, 1);
	curl_setopt($rs, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($rs, CURLOPT_RETURNTRANSFER, 1);
	
	$res = curl_exec($rs);
	curl_close($rs);

	return $res;
}

function array2file($array, $filename)
{
	$fh = fopen($filename, 'a');
	if (!$fh) return false;
	fwrite($fh, serialize($array));
	fclose($fh);
}

function process_csvfile($filename, $languages, $sets) 
{
	$results = array();
	$user = '<username>';
	$pass = '<password>';
	//$set_id = 3;
	$head = array();
	$row = 0;
	$added = 0;
	$maxlen = 0;
	$file = fopen($filename, "r");
	if (!$file) return false;
	$url = "http://$user:$pass@w3.amooma.de/xml-api/voiceprompts/create.xml";

	while (($data = fgetcsv($file, 1024, ";")) !== FALSE) {
		if (count($data) < 2) continue;
		if (($row == 0)) {
			$head = $data;		
			$row++;
			continue;
		}
		$row++;

		$params = array();

		foreach ($head as $index => $param) {
			//if (array_key_exists($index, $data)) $params[$param] = $data[$index];
			$params[$param] =  (array_key_exists($index, $data)) ? $data[$index] : '';
		}
		
		
		$axml = array();
		$prompt = array();
		$prompt['filename'] = $params['filename'];
		$sets_a = explode(',', $sets);
		foreach (explode(',', $languages) as $key => $lang) {
			if (!($set_id = @$sets_a[$key])) break;
			if (@$lang == 'de') $set_id = 3;
			else if ($lang == 'en') $set_id = 4;
			//$set_id = 3;
			$prompt['voiceprompt_set_id'] = $set_id;
			$prompt['text'] = $params[$lang];
			$prompt['lang'] = $lang;
			
			$axml['voiceprompt'] = $prompt;
			
			if ($prompt['text'] != '') {
				$data = array2xml($axml);
				
				$added++;
				echo $prompt['filename'], " : [$set_id] Processing \"".$prompt['text']."\" ...\n";
				//$res = post_xml($url, $data);
				//sleep(2);
				$res = $data;
				$results[] = xml2array($res);
				if (strlen($prompt['text']) > $maxlen) $maxlen = strlen($prompt['text']);
			} else echo " - No string!\n";
		}
	}

	fclose($file);

	array2file($results, 'res_out_'.(time()).'.txt');
	echo "Longest string was $maxlen characters long.\n";
}


process_csvfile('asterisk_core_sounds.csv', 'de,en', '1,2');
process_csvfile('gemeinschaft_sounds.csv', 'de,en', '3,4');

?>
