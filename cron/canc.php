<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');
error_reporting(E_ALL);
include_once '/var/www/newnfe/bootstrap.php';

if (!defined('APP_ROOT')) {
    define('APP_ROOT', '/var/www/newnfe');
}

use NFePHP\NFe\Complements;

$pathCanc = '/var/www/nfe/producao/canceladas';
$pathNFe = '/var/www/nfe/producao/enviadas/aprovadas';
$pathCCe = '/var/www/nfe/producao/cartacorrecao';

//procura por nota no diretorio das canceladas
$aNfe = glob($pathCanc ."/*.xml");
foreach ($aNfe as $pathfile) {
    echo $pathfile . '<br>';
    $filename = basename($pathfile);
    echo $filename;
    if (strpos($pathfile, '-procEventoNfe') === false) {
        unlink($pathfile);
        continue;
    }
    $dom = new \DOMDocument();
    $xml = file_get_contents($pathfile);
    $dom->loadXML($xml);
    //$procEventoNFe = $dom->getElementsByTagName("procEventoNFe")->item(0);
    $retEvento = $dom->getElementsByTagName("retEvento")->item(0);
    $infEvento = $retEvento->getElementsByTagName("infEvento")->item(0);
    $cStat = $infEvento->getElementsByTagName("cStat")->item(0)->nodeValue;
    $chNFe = $infEvento->getElementsByTagName("chNFe")->item(0)->nodeValue;
    $tpEvento = $infEvento->getElementsByTagName("tpEvento")->item(0)->nodeValue;
    $nProt = $infEvento->getElementsByTagName("nProt")->item(0)->nodeValue;
    $anomes = '20' . substr($chNFe,2,4);
    echo "  Tipo : $tpEvento  cStat : $cStat <BR>";
    if ($tpEvento == '110110') {
        //carta de correcao
        if (!is_dir("$pathCCe/$anomes")) {
            if (!mkdir("$pathCCe/$anomes")) {
                echo "Erro - não houve a gravação do cancelamento ! $file <br>";
                exit;
            }
        }
        echo "Carta de Correção $file<br>";
        rename($pathfile,"$pathCCe/$anomes/$filename");
        continue;
    }
    if ($tpEvento != '110111') {
        unlink($pathfile);
        break;
    }
    if ($cStat == '135' || $cStat == '136' || $cStat == '155') {
        //cancelamento aceito
        $nfefile = "$pathNFe/$anomes/$chNFe-nfe.xml";
        //cria o diretorio com anomes
        if (!is_dir("$pathCanc/$anomes/")) {
            if (!mkdir("$pathCanc/$anomes/")) {
                echo "Erro - não houve a criação do diretorio ".$dir.$anomes.DIRECTORY_SEPARATOR;
                exit;
            }
        }
        if (is_file($nfefile)) {
            $nfe = file_get_contents($nfefile);
            $nfecanc = Complements::cancelRegister($nfe, $xml);
            file_put_contents($nfefile, $nfecanc);
            if (!rename($pathfile, "$pathCanc/$anomes/$filename")) {
                echo "Erro - não houve a movimentação do registro do evento !";
                exit;
            } else {
               echo "Movido ! $filename";
            }
        } else {
            echo "ERRO !!! a NFe numero $chNFe de $anomes não foi localizada !!!!";
        }
    }
}
exit;
