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
use NFePHP\NFe\Common\Standardize;

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
        $st = new Standardize();
        $totIcms = 0;
        $totFat = 0;
        $totPeso = 0;
        $totIcms = 0;
        $totFatProd = 0;
        $totPesoProd = 0;
        $totPesoServ = 0;
        $totFatServ = 0;
        foreach ($aList as $file) {
            $xml = file_get_contents($file);
            $std = $st->toStd($xml);
            $dhEmi = !empty($std->NFe->infNFe->ide->dhEmi)
                ? $std->NFe->infNFe->ide->dhEmi
                : $std->NFe->infNFe->ide->dEmi;
            $dt = new \DateTime($dhEmi);
            $tsEmi = $dt->getTimestamp();
            $data = $dt->format('d/m/Y');
            $cStat = !empty($std->protNFe->infProt->cStat)
                ? $std->protNFe->infProt->cStat
                : '';
            
            if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
                self::$nCanc++;
            }
            
            $emitCNPJ = (string) $std->NFe->infNFe->emit->CNPJ;
            $emitRazao = (string) $std->NFe->infNFe->emit->xNome;
            $destRazao = (string) $std->NFe->infNFe->dest->xNome;
            $destUF = (string) $std->NFe->infNFe->dest->enderDest->UF;
            $vNF = (float) $std->NFe->infNFe->total->ICMSTot->vNF;
            $vNFtext = $vNF;
            if (is_numeric($vNF)) {
                $vNFtext = 'R$ '.number_format($vNF, '2', ',', '.');
            }
            $nNF = (string) $std->NFe->infNFe->ide->nNF;
            $natOp = (string) $std->NFe->infNFe->ide->natOp;
            $serie = (string) $std->NFe->infNFe->ide->serie;
            $nProt = (string) $std->protNFe->infProt->nProt;
            
            $nome = $emitRazao;
            if ($emitCNPJ == $cnpj) {
                $nome = $destRazao;
            }
            $email = !empty($std->NFe->infNFe->dest->email) 
                ? $std->NFe->infNFe->dest->email
                : '';
            $aObscont = !empty($std->NFe->infNFe->obsCont)
                ? $std->NFe->infNFe->obsCont
                : [];
            foreach ($aObscont as $obsCont) {
                $xCampo = $obsCont->attributes->xCampo;
                if ($xCampo === 'email') {
                    $email .= ";" . $obsCont->xTexto;
                }
            }
            if (substr($email, 0, 1) == ';') {
               $email = substr($email, 1, strlen($email)-1);
            }
            $vICMS = (float) $std->NFe->infNFe->total->ICMSTot->vICMS;
            
            $valorFat = 0;
            $natOp1 = strtoupper(substr($std->NFe->infNFe->ide->natOp, 0, 1));
            if ($cStat == '100' || $cStat == '150') {
                $cobr = !empty($std->NFe->infNFe->cobr)
                    ? $std->NFe->infNFe->cobr
                    : null;
                if (!empty($cobr)) {
                    $fat = !empty($cobr->fat)
                        ? $cobr->fat
                        : null;
                    $dups = !empty($cobr->dup)
                        ? $cobr->dup
                        : [];
                    if (!empty($fat)) {
                        $valorFat = !empty($fat->vLiq) ? $fat->vLiq : 0; 
                    }
                    if (is_array($dups) && !empty($fat->vLiq)) {
                        if ($valorFat == 0 && count($dups) > 0) {
                            foreach($dups as $dup) {
                                $valorFat += !empty($dup->vDup) ? $dup->vDup : 0;
                            }
                        }
                    } else {
                        if ($valorFat == 0 && !empty($dups)) {
                            $valorFat += !empty($dups->vDup) ? $dups->vDup : 0;
                        }
                    }
                }
                $pesoL = !empty($std->NFe->infNFe->transp->vol->pesoL)
                    ? $std->NFe->infNFe->transp->vol->pesoL
                    : 0;
            }
            if ($natOp1 == 'V') {
                $totFatProd += $valorFat;
                $totPesoProd += $pesoL;
            } elseif ($natOp1 == 'R' && $valorFat > 0) {
                $totFatServ += $valorFat;
                $totPesoServ += $pesoL;
            }    
            $totIcms += $vICMS;
            $totFat += $valorFat;
            $totPeso += $pesoL;
            $aResp[] = [
                'nNF' => $nNF,
                'serie' => $serie,
                'data' =>  $data,
                'nome' => $nome,
                'natureza' => $natOp,
                'cStat' => $cStat,
                'vNF' => $vNFtext,
                'nProt' => $nProt,
                'valorFat' => ($valorFat == 0) ? '' : $valorFat,
                'peso' => ($pesoL == 0) ? '' : $pesoL,
                'email' => $email,
                'uf' => $destUF,
                'icms' => $vICMS
            ];
        }            
        return array(
            'totFat' => $totFatProd,
            'totFatProd' => $totFatProd,
            'totFatServ' => $totFatServ,
            'totPesoProd' => $totPesoProd,
            'totPesoServ' => $totPesoServ,
            'totIcms' => $totIcms,
            'aNF' => $aResp
        );
    }
}
