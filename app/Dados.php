<?php

namespace App;

/**
 * Classe para buscar os documentos destinados
 * 
 * @category   Application
 * @package    robmachado\teste
 * @copyright  Copyright (c) 2008-2015
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm at gmail dot com>
 * @link       http://github.com/robmachado/teste for the canonical source repository
 */

use NFePHP\Common\DOMImproved as Dom;

class Dados
{
    public static $nCanc = 0;
    
    public static function extraiResumo($aList)
    {
        $aResp = array();
        foreach ($aList as $file) {
            $dom = null;
            try {
                //podem ser xml com
                //resumo *-resNFe.xml
                //cancelamento *-cancNFe.xml
                //carta de correção *-cce.xml
                $pos = explode('-', $file);
                $dom = new Dom();
                $dom->load($file);
                switch ($pos[1]) {
                    case 'resNFe.xml':
                        $dt = new \DateTime($dom->getNodeValue('dhEmi'));
                        $dataemi = $dt->format('d/m/Y');
                        $aResp[] = array(
                            'tipo' => 'NFe',
                            'chNFe' => $dom->getNodeValue('chNFe'),
                            'cnpj' => $dom->getNodeValue('CNPJ'),
                            'cpf' => $dom->getNodeValue('CPF'),
                            'xNome' => $dom->getNodeValue('xNome'),
                            'tpNF' => $dom->getNodeValue('tpNF'),
                            'vNF' => $dom->getNodeValue('vNF'),
                            'digval' => $dom->getNodeValue('digVal'),
                            'nprot' => $dom->getNodeValue('nProt'),
                            'cSitNFe' => $dom->getNodeValue('cSitNFe'),
                            'dhEmi' => $dataemi,
                            'dhRecbto' => $dom->getNodeValue('dhRecbto')
                        );
                        break;
                    case 'cancNFe.xml':
                        $aResp[] = array(
                            'tipo' => 'Cancelamento',
                            'chNFe' => $file,
                            'cnpj' => '',
                            'cpf' => '',
                            'xNome' => 'Cancelamento',
                            'tpNF' => '',
                            'vNF' => '',
                            'digval' => '',
                            'nprot' => '',
                            'cSitNFe' => '',
                            'dhEmi' => '',
                            'dhRecbto' => ''
                        );
                        break;
                    case 'cce.xml':
                        $aResp[] = array(
                            'tipo' => 'CCe',
                            'chNFe' => $file,
                            'cnpj' => '',
                            'cpf' => '',
                            'xNome' => 'CCe',
                            'tpNF' => '',
                            'vNF' => '',
                            'digval' => '',
                            'nprot' => '',
                            'cSitNFe' => '',
                            'dhEmi' => '',
                            'dhRecbto' => ''
                        );
                        break;
                }
            } catch (RuntimeException $e) {
                $aResp[] = array(
                    'chNFe' => '',
                    'cnpj' => '',
                    'cpf' => '',
                    'xNome' => $file,
                    'tpNF' => '',
                    'vNF' => '',
                    'digval' => '',
                    'nprot' => '',
                    'cSitNFe' => '3',
                    'dhEmi' => '',
                    'dhRecbto' => ''
                );
                
            }
        }
        return $aResp;
    }
    
    public static function extrai($aList, $cnpj = '')
    {
        $aResp = array();
        $totFat = 0;
        $totPeso = 0;
        $totIcms = 0;
        $totFatProd = 0;
        $totPesoProd = 0;
        $totPesoServ = 0;
        $totFatServ = 0;
        foreach ($aList as $file) {
            $dom = null;
            $ide = null;
            $emit = null;
            $dest = null;
            try {
                $dom = new Dom();
                $dom->load($file);
                $ide = $dom->getNode('ide');
                $emit = $dom->getNode('emit');
                $dest = $dom->getNode('dest');
                $enderDest = $dom->getNode('enderDest');
                $fat = $dom->getNode('fat');
                $icmsTot = $dom->getNode('ICMSTot');
                $vol = $dom->getNode('vol');
                $cStat = $dom->getNodeValue('cStat');
                if ($cStat != '100') {
                    self::$nCanc++;
                }
                $dhEmi = $dom->getValue($ide, 'dhEmi');
                if (empty($dhEmi)) {
                    $dhEmi = $dom->getValue($ide, 'dEmi');
                }
                $dt = new \DateTime($dhEmi);
                $tsEmi = $dt->getTimestamp();
                $data = '';
                if (is_numeric($tsEmi)) {
                    $data = date('d/m/Y', $tsEmi);
                }
                $emitCNPJ = $dom->getValue($emit, 'CNPJ');
                $emitRazao = $dom->getValue($emit, 'xNome');
                $destRazao = $dom->getValue($dest, 'xNome');
                $destUF = $dom->getValue($enderDest, 'UF');
                $vNF = $dom->getValue($icmsTot, 'vNF');
                $vNFtext = $vNF;
                if (is_numeric($vNF)) {
                    $vNFtext = 'R$ '.number_format($vNF, '2', ',', '.');
                }
                $serie = $dom->getNodeValue('serie');
                $nProt = $dom->getNodeValue('nProt');
                $nome = $emitRazao;
                if ($emitCNPJ == $cnpj) {
                    $nome = $destRazao;
                }
                $email = $dom->getValue($dest, 'email');
                $aObscont = $dom->getElementsByTagName('obsCont');
                if (count($aObscont) > 0) {
                    foreach ($aObscont as $obsCont) {
                        $xCampo = $obsCont->getAttribute('xCampo');
                        if ($xCampo == 'email') {
                            $email .= ";" . $dom->getValue($obsCont, 'xTexto');
                        }
                    }
                }
                if (substr($email, 0, 1) == ';') {
                    $email = substr($email, 1, strlen($email)-1);
                }
                $vICMS = $dom->getValue($icmsTot, 'vICMS');
                $totIcms += $vICMS;
                $valorFat = 0;
                $pesoLProd = 0;
                $pesoLServ = 0;
                $nO = substr($dom->getValue($ide, 'natOp'), 0, 1);    
                if ($cStat == '100' || $cStat == '150') {
                    $valorFat = 0;
                    if (!is_string($fat)) {
                        $valorFat = (float) $dom->getValue($fat, 'vLiq');
                    }
                    if ($nO === 'V') {
                        if ($valorFat > 0) {
                            if (substr($destRazao, 0, 6) !== 'TATICA' ) {
                                $pesoLProd = $dom->getValue($vol, 'pesoL');
                                $totFatProd += $valorFat;
                            } else {
                                $valorFat = 0;
                            }
                        }        
                    } elseif ($nO === 'R') {
                        if ($valorFat > 0) {
                            $pesoLServ = $dom->getValue($vol, 'pesoL');
                            if (is_numeric($valorFat)) {
                                $totFatServ += $valorFat;
                            }    
                        }
                    }
                    
                }
                $totPesoProd += $pesoLProd;
                $totPesoServ += $pesoLServ;
                $pesoL = $pesoLProd + $pesoLServ;
                $aResp[] = array(
                    'nNF' => $dom->getValue($ide, 'nNF'),
                    'serie' => $serie,
                    'data' =>  $data,
                    'nome' => $nome,
                    'natureza' => $dom->getValue($ide, 'natOp'),
                    'cStat' => $cStat,
                    'vNF' => $vNFtext,
                    'nProt' => $nProt,
                    'valorFat' => ($valorFat == 0) ? '' : $valorFat,
                    'peso' => ($pesoL == 0) ? '' : $pesoL,
                    'email' => $email,
                    'uf' => $destUF,
                    'icms' => $vICMS
                );
            } catch (RuntimeException $e) {
                $aResp[] = array(
                    'nNF' => '000000',
                    'serie' => '000',
                    'data' =>  '000',
                    'nome' => 'FALHA',
                    'natureza' => "$file",
                    'cStat' => '',
                    'vNF' => 0,
                    'nProt' => '',
                    'valorFat' => '',
                    'email' => '',
                    'uf' => '',
                    'icms' => 0
                );
            }
        }
        return array(
            'totFat' => $totFatProd + $totFatServ,
            'totFatProd' => $totFatProd,
            'totFatServ' => $totFatServ,
            'totPesoProd' => $totPesoProd,
            'totPesoServ' => $totPesoServ,
            'totIcms' => $totIcms,
            'aNF' => $aResp
        );
    }
}
