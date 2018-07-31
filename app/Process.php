<?php

namespace App;

use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;
use NFePHP\Common\Validator;

class Process
{
    public $tools;
    public $config;
    public $certificate;

    public function __construct()
    {
        
        $pathcert = realpath(dirname(__FILE__) . '/../certs');
        $content = file_get_contents($pathcert . '/' . $_ENV['CERTIFICATE']);
        
        $this->certificate = Certificate::readPfx($content, $_ENV['PASSWORD']);
        
        $config = [
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => (int) $_ENV['NFE_TPAMB'],
            "razaosocial" => $_ENV['NFE_RAZAO'],
            "siglaUF" => $_ENV['NFE_UF'],
            "cnpj" => $_ENV['NFE_CNPJ'],
            "schemes" => $_ENV['NFE_SCHEMA'],
            "versao" => $_ENV['NFE_VERSAO'],
            "tokenIBPT" => $_ENV['NFE_IBPT'],
            "CSC" => "",
            "CSCid" => "",
            "aProxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ];
        $this->config = json_encode($config);
        $this->tools = new Tools($this->config, $this->certificate);
    }
    
    public function send($filename)
    {
        $st = new Standardize();
        $xml = file_get_contents($_ENV['SIGNFOLDER'] . '/' . $filename);
        //é uma NFe ?
        $tag = $st->whichIs($xml);
        if ($tag !== 'NFe') {
            throw new Exception('Esse documento não é uma NFe assinada.');
        }
        //está assinado ?
        if (!$this->isSigned) {
            throw new Exception('Esse documento é uma NFe, mas não está assinado.');
        }
        //verifica se já foi enviado
        if (!is_file($_ENV['TEMPFOLDER'] . "/$filename-nfe-recibo.xml")) {
            //envia para a SEFAZ
            $lote = date('Ymdhis');
            $resp = $tools->sefazEnviaLote([$xml], $lote);
            file_put_contents($_ENV['TEMPFOLDER'] . "/$filename-nfe-recibo.xml", $recibo);
        } else {
            $resp = file_get_contents($_ENV['TEMPFOLDER'] . "/$filename-nfe-recibo.xml");
        }    
        $std = $st->toStd($resp);
        if ($std->cStat == '103') {
            $recibo = $std->infRec->nRec;
            $protStat = '';
            $limit = 3;
            $c = 1;
            while ($proStat != '104') {
                $c++;
                if ($c > $limit) {
                    //como não houve resposta o recibo é mantido
                    $message = [2, "Tempo limite excedido."];
                    break;
                }
                $protocolo = $tools->sefazConsultaRecibo($recibo);
                file_put_contents($_ENV['TEMPFOLDER'] . "/$filename-nfe-protocolo.xml", $protocol);
                $prot = $st->toStd($protocolo);
                $proStat = $prot->cStat;
                if ($proStat == '104') {
                    $xmlProtocolado = Complements::toAuthorize($xml, $protocolo);
                    file_put_contents($_ENV['SENDEDFOLDER'] . "/$filename-procNFe.xml", $xmlProtocolado);
                    $message = [1, "Autorizada!"];
                    break;
                } elseif ($proStat > '105') {
                    //como houve resposta de ERRO o recibo é modificado para permitir o reenvio de nova nfe corrigida
                    rename($_ENV['TEMPFOLDER'] . "/$filename-nfe-recibo.xml", $_ENV['TEMPFOLDER'] . "/$filename-nfe-recibo_chk.xml");
                    $message = [0, "ERROR: [$proStat] $prot->xMotivo"];
                    break;
                }
                sleep(2); 
            }
        } else {
            $message = [0, "ERROR: [$proStat] $prot->xMotivo"];
        }
        return $message;
    }
    
    protected function isSigned($content)
    {
        if (!Validator::isXML($content)) {
            throw new Exception('Esse arquivo não é um XML válido.');
        }
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($content);
        $signature = $dom->getElementsByTagName('Signature')->item(0);
        return !empty($signature);
    }    
}
