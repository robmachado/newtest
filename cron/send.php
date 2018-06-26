<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
include_once '../bootstrap.php';

use NFePHP\Common\DOMImproved as Dom;
use NFePHP\DA\NFe\Danfe;
use App\Mail;
//use App\DBase;

//$db = new DBase(
//    $_ENV['DBTYPE'],
//    $_ENV['DBHOST'],
//    $_ENV['DBPORT'],
//    $_ENV['DBNAME'],
//    $_ENV['DBUSER'],
//    $_ENV['DBPASS']
//);
//$dbh = $db->connect();

$dir = realpath($_ENV['NFEFOLDER']."/../") . "/*.xml";
$listas = glob($dir);
foreach ($listas as $file) {
    $xml = file_get_contents($file);
    $dom = new Dom();
    $dom->load($file);
    $nfeProc = $dom->getElementsByTagName("nfeProc")->item(0);
    $infNFe = $dom->getElementsByTagName("infNFe")->item(0);
    $ide = $dom->getElementsByTagName("ide")->item(0);
    $emit = $dom->getElementsByTagName("emit")->item(0);
    $dest = $dom->getElementsByTagName("dest")->item(0);
    $infAdic = $dom->getElementsByTagName("infAdic")->item(0);
    $obsCont = $dom->getElementsByTagName("obsCont");
    $ICMSTot = $dom->getElementsByTagName("ICMSTot")->item(0);
    $razao = utf8_decode($dest->getElementsByTagName("xNome")->item(0)->nodeValue);
    $cnpj = $dest->getElementsByTagName("CNPJ")->item(0)->nodeValue;
    $anomes = substr($ide->getElementsByTagName('dhEmi')->item(0)->nodeValue, 0, 4) . substr($ide->getElementsByTagName('dhEmi')->item(0)->nodeValue, 5, 2);
    $numero = str_pad($ide->getElementsByTagName('nNF')->item(0)->nodeValue, 9, "0", STR_PAD_LEFT);
    $serie = str_pad($ide->getElementsByTagName('serie')->item(0)->nodeValue, 3, "0", STR_PAD_LEFT);
    $emitente = utf8_decode($emit->getElementsByTagName("xNome")->item(0)->nodeValue);
    $vtotal = number_format($ICMSTot->getElementsByTagName("vNF")->item(0)->nodeValue, 2, ",", ".");
    $nfe_chave = str_replace('NFe', '', $infNFe->getAttribute("Id"));
    $nfe_nNF = $ide->getElementsByTagName('nNF')->item(0)->nodeValue;
    $nfe_serie = $ide->getElementsByTagName('serie')->item(0)->nodeValue;
    $dhEmi = $ide->getElementsByTagName("dhEmi")->item(0)->nodeValue;
    $aDhEmi = explode('-', $dhEmi);
    $nfe_dEmi = str_replace('T', ' ', $aDhEmi[0] . '-' . $aDhEmi[1] . '-' . $aDhEmi[2]);
    $nfe_tpNF = $ide->getElementsByTagName("tpNF")->item(0)->nodeValue;
    $nfe_cNF = $ide->getElementsByTagName("cNF")->item(0)->nodeValue;
    $nfe_cDV = $ide->getElementsByTagName("cDV")->item(0)->nodeValue;
    $nfe_versao = $infNFe->getAttribute("versao");
    $nfe_cUF = $ide->getElementsByTagName("cUF")->item(0)->nodeValue;
    $nfe_tpImp = $ide->getElementsByTagName("tpImp")->item(0)->nodeValue;
    $nfe_tpAmb = $ide->getElementsByTagName("tpAmb")->item(0)->nodeValue;
    $nfe_tpEmis = $ide->getElementsByTagName("tpEmis")->item(0)->nodeValue;
    $nfe_finNFe = $ide->getElementsByTagName("finNFe")->item(0)->nodeValue;
    $nfe_procEmi = $ide->getElementsByTagName("procEmi")->item(0)->nodeValue;
    $nfe_verProc = $ide->getElementsByTagName("verProc")->item(0)->nodeValue;
    $destinatario = utf8_decode($dest->getElementsByTagName("xNome")->item(0)->nodeValue);
    $contato = '';
    $addresses = [];
    //buscar emails
    $emailaddress = !empty($dest->getElementsByTagName("email")->item(0)->nodeValue) ? utf8_decode($dest->getElementsByTagName("email")->item(0)->nodeValue) : '';
    if (strtoupper(trim($emailaddress)) == 'N/D' || strtoupper(trim($emailaddress)) == '') {
        $emailaddress = '';
    } else {
        $emailaddress = trim($emailaddress);
        $emailaddress = str_replace(';', ',', $emailaddress);
        $emailaddress = str_replace(':', ',', $emailaddress);
        $emailaddress = str_replace('/', ',', $emailaddress);
        $addresses = explode(',', $emailaddress);
    }
    if (isset($obsCont)) {
        $i = 0;
        foreach ($obsCont as $obs) {
            $campo = $obsCont->item($i)->getAttribute("xCampo");
            $xTexto = !empty($obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue) ? $obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue : '';
            if (substr($campo, 0, 5) == 'email' && $xTexto != '') {
                $xTexto = str_replace(';', ',', $xTexto);
                $xTexto = str_replace(':', ',', $xTexto);
                $xTexto = str_replace('/', ',', $xTexto);
                $aTexto = explode(',', $xTexto);
                foreach ($aTexto as $t) {
                    $addresses[] = $t;
                }
            }
            $i++;
        }
    }
    $addresses[] = $_ENV['MAILREPLY'];
    $resp = 'automatico';
    if (isset($nfeProc)) {
        $nfe_nProt = !empty($nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue) ? $nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue : '';
        $nfe_dhRecbto = !empty($nfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue) ? $nfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue : '';
        $nfe_cStat = !empty($nfeProc->getElementsByTagName("cStat")->item(0)->nodeValue) ? $nfeProc->getElementsByTagName("cStat")->item(0)->nodeValue : '';
        $dt = new \DateTime($nfe_dhRecbto);
        $nfe_dhRecbto = $db->ts2win($dt->format('Y-m-d'));
        $nfe_mail = '0';
        if ($nfe_cStat == '100') {
            //100 Autorizado o uso da NF-e
            $nfe_status = 'A';
        }
        if ($nfe_cStat == '101') {
            //101 Cancelamento de NF-e homologado
            $nfe_status = 'C';
        }
        if ($nfe_cStat == '110' || $nfe_cStat == '301' || $nfe_cStat == '302') {
            //110 Uso Denegado
            $nfe_status = 'D';
        }
        if ($nfe_cStat > '200' && $nfe_cStat != '301' && $nfe_cStat != '302') {
            $nfe_status = 'R';
        }
    } else {
        $nfe_nProt = '';
        $nfe_dhRecbto = '1900-01-01 00:00:00';
        $nfe_cStat = '000';
        $nfe_status = '';
        $nfe_mail = '0';
    }
    if (!empty($addresses)) {
        //inicializar a DANFE
        $danfe = new Danfe($xml, 'P', 'A4', '/var/www/newnfe/publico/images/logo.jpg', 'I', '');
        //error_reporting(E_ALL);ini_set('display_errors', 'On');
        //montar o PDF e o nome do arquivo PDF
        $nome = $danfe->monta();
        $nomePDF = $nome . '.pdf';
        $nomeXML = $nome . '-nfe.xml';
        //carregar o arquivo pdf numa variavel
        $pdf = $danfe->render();
        //enviar o email e testar
        $nfe_mail_log = '';
        $mail = new Mail();
        $resp = $mail->envia($xml, $addresses, true, $pdf);
    }
    //$sqlComm = "SELECT nfe_id, nfe_nNF FROM ACO_nfe_$nfe_tpAmb WHERE nfe_nNF = '" . $nfe_nNF . "'";
    //$aRet = $db->querySQL($dbh, $sqlComm);
    //if (count($aRet) > 0) {
        $flagINC = FALSE;
    //} else {
    //    $flagINC = TRUE;
    //}
    if ($flagINC) {
        $sqlComm = "INSERT INTO ACO_nfe_$nfe_tpAmb ( nfe_id, nfe_nNF, nfe_serie, nfe_cNF, nfe_cDV, nfe_dEmi, nfe_chave, nfe_versao, nfe_cUF, nfe_tpNF, nfe_tpImp, nfe_tpEmis, nfe_finNFe, nfe_procEmi, nfe_verProc, nfe_nProt, nfe_dhRecbto, nfe_cStat, nfe_status,";
        $sqlComm .= "nfe_mail, nfe_mail_error, resp ) VALUES ( ";
        $sqlComm .= "'" . $nfe_nNF . "',";
        $sqlComm .= "'" . $nfe_nNF . "',";
        $sqlComm .= "'" . $nfe_serie . "',";
        $sqlComm .= "'" . $nfe_cNF . "',";
        $sqlComm .= "'" . $nfe_cDV . "',";
        $sqlComm .= "'" . $nfe_dEmi . "',";
        $sqlComm .= "'" . $nfe_chave . "',";
        $sqlComm .= "'" . $nfe_versao . "',";
        $sqlComm .= "'" . $nfe_cUF . "',";
        $sqlComm .= "'" . $nfe_tpNF . "',";
        $sqlComm .= "'" . $nfe_tpImp . "',";
        $sqlComm .= "'" . $nfe_tpEmis . "',";
        $sqlComm .= "'" . $nfe_finNFe . "',";
        $sqlComm .= "'" . $nfe_procEmi . "',";
        $sqlComm .= "'" . $nfe_verProc . "',";
        $sqlComm .= "'" . $nfe_nProt . "',";
        $sqlComm .= "'" . $nfe_dhRecbto . "',";
        $sqlComm .= "'" . $nfe_cStat . "',";
        $sqlComm .= "'" . $nfe_status . "',";
        //$sqlComm .= "'" . $docXML . "',";
        $sqlComm .= "'" . $nfe_mail . "',";
        $sqlComm .= "'" . $nfe_mail_log . "',";
        $sqlComm .= "'" . $resp . "')";
    } else {
        $sqlComm = "UPDATE ACO_nfe_$nfe_tpAmb SET ";
        $sqlComm .= "nfe_id ='" . $nfe_nNF . "',";
        $sqlComm .= "nfe_nNF ='" . $nfe_nNF . "',";
        $sqlComm .= "nfe_serie ='" . $nfe_serie . "',";
        $sqlComm .= "nfe_cNF='" . $nfe_cNF . "',";
        $sqlComm .= "nfe_cDV='" . $nfe_cDV . "',";
        $sqlComm .= "nfe_dEmi='" . $nfe_dEmi . "',";
        $sqlComm .= "nfe_chave='" . $nfe_chave . "',";
        $sqlComm .= "nfe_versao='" . $nfe_versao . "',";
        $sqlComm .= "nfe_cUF='" . $nfe_cUF . "',";
        $sqlComm .= "nfe_tpNF='" . $nfe_tpNF . "',";
        $sqlComm .= "nfe_tpImp='" . $nfe_tpImp . "',";
        $sqlComm .= "nfe_tpEmis='" . $nfe_tpEmis . "',";
        $sqlComm .= "nfe_finNFe='" . $nfe_finNFe . "',";
        $sqlComm .= "nfe_procEmi='" . $nfe_procEmi . "',";
        $sqlComm .= "nfe_verProc='" . $nfe_verProc . "',";
        $sqlComm .= "nfe_nProt='" . $nfe_nProt . "',";
        $sqlComm .= "nfe_dhRecbto='" . $nfe_dhRecbto . "',";
        $sqlComm .= "nfe_cStat='" . $nfe_cStat . "',";
        $sqlComm .= "nfe_status='" . $nfe_status . "',";
        //$sqlComm .= "nfe_xmlfile='" . $docXML . "',";
        $sqlComm .= "nfe_mail='" . $nfe_mail . "',";
        $sqlComm .= "nfe_mail_error='" . $nfe_mail_log . "',";
        $sqlComm .= "resp='" . $resp . "' ";
        $sqlComm .= "WHERE nfe_id ='" . $nfe_nNF . "';";
    }
    //$num = $db->execSQL($dbh, $sqlComm);
    $dirdest = $_ENV['NFEFOLDER'] . "/$anomes";
    if (!is_dir($dirdest)) {
        mkdir($dirdest, 0777);
    }
    $novonome = $nfe_chave . '-nfe.xml';
    rename($file, $dirdest.'/'.$novonome);
}
$dbh = null;
exit;