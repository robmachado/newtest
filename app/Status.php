<?php

namespace App;

/**
 * Classe para buscar o status da SEFAZ e a validade do certificado digital
 * 
 * @category   Application
 * @package    robmachado\teste
 * @copyright  Copyright (c) 2008-2015
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm at gmail dot com>
 * @link       http://github.com/robmachado/teste for the canonical source repository
 */

use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;
use App\Process;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__FILE__)));
}

class Status
{
    
    /**
     * verifica
     * Verifica o status da SEFAZ com o webservice e retorna tags html com o resultado
     * @param string $config json do arquivo de configuração
     * @return string
     */
    public static function verifica()
    {
        $process = new Process();
        $aRetorno = array();
        $tstmp = 0;
        if (is_file(APP_ROOT.'/base/status.json')) {
            $std = json_decode(file_get_contents(APP_ROOT.'/base/status.json'));
            $dt = new \DateTime($std->dhRecbto);
            $tstmp = $dt->getTimestamp();
        }
        $tsnow = time();
        $dif = ($tsnow - $tstmp);
        //caso tenha passado mais de uma hora desde a ultima verificação
        $certificate = $process->certificate;
        $certTS = $certificate->getValidTo()->getTimestamp();
        if ($dif > 3600) {
            $tools = $process->tools;
            $resp = $tools->sefazStatus($_ENV['NFE_UF'], $_ENV['NFE_TPAMB']);
            $st = new \NFePHP\NFe\Common\Standardize();
            $json = $st->toJson($resp);
            file_put_contents(APP_ROOT.'/base/status.json', $json);
            $std = json_decode($json);
        }
        $dttmp = new \DateTime($std->dhRecbto);
        $dhora = $dttmp->format('d/m/Y');
        $htmlStatus = "<p class=\"smallred\">OFF-LINE</p>\n<p class=\"smallred\">$dhora</p>";
        if ($std->cStat == '107') {
            $htmlStatus = "<p class=\"smallgreen\">SEFAZ On-Line (layout ". $_ENV['NFE_VERSAO'] . ")</p>\n<p class=\"smallgreen\">$dhora</p>";
        }
        $data = date('d/m/Y', $certTS);
        $hoje = date('Y-m-d');
        $diferenca = $certTS - strtotime($hoje);
        $dias = floor($diferenca / (60 * 60 * 24));
        $htmlCert = "<p class=\"smallgreen\">Certificado expira em $dias dias [$data]</p>";
        if ($dias < 31) {
            $htmlCert = "<p class=\"smallred\">Certificado expira em $dias dias [$data]</p>";
        }
        return [$htmlStatus, $htmlCert];
    }
}
