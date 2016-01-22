	<?php
/**
* Telegram Bot Patrimoni Trasparenti Lic. MIT . Dati Lic. CC-BY-SA openPolis
* @author Francesco Piero Paolicelli @piersoft derivato da parte di codice di @emergenzaprato
*/

include("Telegram.php");
include("settings_t.php");

class mainloop{
const MAX_LENGTH = 4096;
function start($telegram,$update)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];

	$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg);
	$db = NULL;

}

//gestisce l'interfaccia utente
 function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg)
{
	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	if ($text == "/start" || $text == "Informazioni") {
		$img = curl_file_create('logo.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$reply = "Benvenuto. Questo è un servizio automatico (bot da Robot) per i dati raccolti su ".NAME.". In questo bot puoi ricercare i parlamentari eletti per Cognome oppure cliccare su Filtro o Ricerca per istruzioni. In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.Questo bot è stato realizzato da @piersoft. Il progetto e il codice sorgente sono liberamente riutilizzabili con licenza MIT. I dati utilizzati sono presenti su http://patrimoni.openpolis.it con lic. CC-BY-SA";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$log=$today. ",new_info,," .$chat_id. "\n";
		file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

		$this->create_keyboard_temp($telegram,$chat_id);
		exit;
	}
			elseif ($text == "Ricerca") {
				$reply = "Scrivi il Cognome da cercare anteponendo il carattere - , ad esempio: -Renzi";
				$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
		//		$log=$today. ";new chat started;" .$chat_id. "\n";
				$this->create_keyboard_temp($telegram,$chat_id);
exit;

}elseif($location!=null)
		{
		//	$this->location_manager($telegram,$user_id,$chat_id,$location);
		//	exit;
		}

		elseif(strpos($text,'/') === false){

			if(strpos($text,'?') !== false || strpos($text,'-') !== false || strpos($text,'i:') !== false || strpos($text,'c:') !== false){
				$text=str_replace("?","",$text);
				$text=str_replace("-","",$text);
				$filtro="upper(C)";
				$location="Sto cercando il parlamentare nel cui cognome è presente: ".$text;

				if (strpos($text,'i:') !== false){
					$text=str_replace("i:","",$text);
					$location="Sto cercando i parlamentari eletti con incarico: ".$text;
					$filtro="upper(F)";
				}else if (strpos($text,'c:') !== false){
					$text=str_replace("c:","",$text);
					$location="Sto cercando i parlamentari eletti nella circoscrizione: ".$text;
					$filtro="upper(I)";
				}
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				$text=str_replace(" ","%20",$text);
				$text=strtoupper($text);
		//		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20A%2CC%2CD%2CG%2CH%2CP%2CL%2CM%2CO%2CJ%2CK%20WHERE%20upper(C)%20like%20%27%25";
				$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20".$filtro."%20like%20%27%25";
				$urlgd .=$text;
				$urlgd .="%25%27%20AND%20N%20IS%20NOT%20NULL&key=".GDRIVEKEY."&gid=".GDRIVEGID2;
				$inizio=1;
				$homepage ="";
				$csv = array_map('str_getcsv',file($urlgd));
				$csv=str_replace(array("\r", "\n"),"",$csv);
				$count = 0;
				foreach($csv as $data=>$csv1){
					$count = $count+1;
				}
				if ($count ==0){
						$location="Nessun risultato trovato";
						$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
						$telegram->sendMessage($content);
					}
					if ($count >200){
							$location="Troppe risposte per il criterio scelto. Ti preghiamo di fare una ricerca più circoscritta";
							$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
							$telegram->sendMessage($content);
							exit;
						}
					function decode_entities($text) {

													$text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
												$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
													$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
												$text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
													return $text;
					}
						$homepage .="\n";
				for ($i=$inizio;$i<$count;$i++){

					$homepage .="\n";
					$homepage .=strtoupper($csv[$i][1])." ".strtoupper($csv[$i][2])."\n";
					$homepage .="Per i dettagli digita: ".$csv[$i][0]."\n";
					$homepage .="____________\n";

				}
				$chunks = str_split($homepage, self::MAX_LENGTH);
				foreach($chunks as $chunk) {
					$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
						}
						$log=$today. ",ricerca,".$text."," .$chat_id. "\n";
						file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

		}else if (strpos($text,'Filtro') !== false){
		//	$text=str_replace("?","",$text);
			$location="Puoi fare una ricerca filtrata:\nPer circoscrizione scrivi c:regione esempio c:umbria\nPer incarico scrivi i:incarico esempio i:camera (a scelta tra camera,senato,governo)";
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
	//	$text=str_replace(" ","%20",$text);
//			$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20A%2CC%2CD%2CG%2CH%2CP%2CL%2CM%2CO%2CJ%2CK%20WHERE%20N%20IS%20NOT%20NULL";


		}elseif (strpos($text,'1') !== false || strpos($text,'2') !== false || strpos($text,'3') !== false || strpos($text,'4') !== false || strpos($text,'5') !== false || strpos($text,'6') !== false || strpos($text,'7') !== false || strpos($text,'8') !== false || strpos($text,'9') !== false || strpos($text,'0') !== false ){
			$location="Sto cercando l'ID: ".$text;
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
	//		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20A%2CC%2CD%2CG%2CH%2CP%2CL%2CM%2CO%2CJ%2CK%20WHERE%20A%20%3D%20";
			$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20A%20%3D%20";

			$urlgd .=$text;
			$urlgd .="%20&key=".GDRIVEKEY."&gid=".GDRIVEGID2;
			$inizio=1;
			$homepage ="";
			$csv = array_map('str_getcsv',file($urlgd));
			$csv=str_replace(array("\r", "\n"),"",$csv);

			$count = 0;
			foreach($csv as $data=>$csv1){
				$count = $count+1;
			}
		if ($count ==0 || $count ==1){
					$location="Nessun risultato trovato";
					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
				}
				function decode_entities($text) {

												$text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
											$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
												$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
											$text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
	return $text;
				}
			for ($i=$inizio;$i<$count;$i++){

				$thumb="http://op_openparlamento_images.s3.amazonaws.com/parlamentari/thumb/".$csv[1][0].".jpeg";
				$ch = curl_init($thumb);
				$urlfile="log/temp".$csv[1][0].".png";
				$fp = fopen($urlfile, 'wb');
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_exec($ch);
				curl_close($ch);
				fclose($fp);
				$img = curl_file_create($urlfile,'image/png');
				$contentp = array('chat_id' => $chat_id, 'photo' => $img);
				$telegram->sendPhoto($contentp);
				$homepage .="\n";
		if($csv[$i][1] !=NULL)	$homepage .="Nome: ".$csv[$i][1]."\n";
		if($csv[$i][2] !=NULL)		$homepage .="Cognome: ".$csv[$i][2]."\n";
		if($csv[$i][3] !=NULL)		$homepage .="Data di nascita: ".$csv[$i][3]."\n";
		if($csv[$i][4] !=NULL)		$homepage .="Professione: ".$csv[$i][4]."\n";
		if($csv[$i][5] !=NULL)		$homepage .="Incarico: ".$csv[$i][5]."\n";
		if($csv[$i][6] !=NULL)		$homepage .="Lista di elezione/partito: ".$csv[$i][6]."\n";
		if($csv[$i][7] !=NULL)		$homepage .="Gruppo parlamentare: ".$csv[$i][7]."\n";
		if($csv[$i][8] !=NULL)		$homepage .="Circoscrizione di elezione: ".$csv[$i][8]."\n";
		if($csv[$i][9] !=NULL)		$homepage .="Totale 730 dichiarato: ".$csv[$i][9]."\n";
		if($csv[$i][10] !=NULL)		$homepage .="Totale 730 coniuge: ".$csv[$i][10]."\n";
		if($csv[$i][11] !=NULL)		$homepage .="Totale contributi: ".$csv[$i][11]."\n";
		if($csv[$i][12] !=NULL)		$homepage .="Totale spese elettorali: ".$csv[$i][12]."\n";
		if($csv[$i][13] !=NULL)		$homepage .="Indice di completezza: ".$csv[$i][13]."\n";
		if($csv[$i][14] !=NULL)		$homepage .="Note completezza: ".$csv[$i][14]."\n";
		if($csv[$i][15] !=NULL)		$homepage .="Num. fabbricati: ".$csv[$i][15]."\n";
		if($csv[$i][16] !=NULL)		$homepage .="Num. terreni: ".$csv[$i][16]."\n";
		if($csv[$i][17] !=NULL)		$homepage .="Num. beni mobili: ".$csv[$i][17]."\n";
		if($csv[$i][18] !=NULL)		$homepage .="Num. di partecipazioni (azioni/quote) in società: ".$csv[$i][18]."\n";
		if($csv[$i][19] !=NULL)		$homepage .="Num. di incarichi di amministratore di società: ".$csv[$i][19]."\n";
		$csv[$i][2]=str_replace(" ","-",$csv[$i][2]);
		$csv[$i][1]=str_replace(" ","-",$csv[$i][1]);

				$homepage .="Dettagli completi su:\nhttp://patrimoni.openpolis.it/#/scheda/".$csv[$i][2]."-".$csv[$i][1]."/".$csv[$i][0];
				$homepage .="\n____________\n";
		}
		$chunks = str_split($homepage, self::MAX_LENGTH);
		foreach($chunks as $chunk) {
			$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
				}

				$log=$today. ",ricerca,".$text."," .$chat_id. "\n";
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
		}
		$this->create_keyboard_temp($telegram,$chat_id);
exit;
}

	}

	function create_keyboard_temp($telegram, $chat_id)
	 {
			 $option = array(["Filtro","Ricerca"],["Informazioni"]);
			 $keyb = $telegram->buildKeyBoard($option, $onetime=false);
			 $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[Digita l'ID,fai una ricerca con - oppure con c: o i: ]");
			 $telegram->sendMessage($content);
	 }

}

?>
