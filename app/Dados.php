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
        $aResp = [];
        foreach ($aList as $file) {
            $xml = file_get_contents($file);
            $std = $st->toStd($xml);
            if (isset($std->NFe)) {
                $nfe = $std->NFe;
            } else {
                $nfe = $std;
            }
            if (!isset($nfe->infNFe)) {
                unlink($file);
                continue;
            }
            $dhEmi = !empty($nfe->infNFe->ide->dhEmi)
                ? $nfe->infNFe->ide->dhEmi
                : $nfe->infNFe->ide->dEmi;
            $dt = new \DateTime($dhEmi);
            $tsEmi = $dt->getTimestamp();
            $data = $dt->format('d/m/Y');
            $cStat = !empty($std->protNFe->infProt->cStat)
                ? $std->protNFe->infProt->cStat
                : '';
            
            if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
                self::$nCanc++;
            }
            
            $emitCNPJ = (string) $nfe->infNFe->emit->CNPJ;
            $emitRazao = (string) $nfe->infNFe->emit->xNome;
            $destRazao = (string) $nfe->infNFe->dest->xNome;
            $destUF = (string) $nfe->infNFe->dest->enderDest->UF;
            $vNF = (float) $nfe->infNFe->total->ICMSTot->vNF;
            $vNFtext = $vNF;
            if (is_numeric($vNF)) {
                $vNFtext = 'R$ '.number_format($vNF, '2', ',', '.');
            }
            $nNF = (string) $nfe->infNFe->ide->nNF;
            $natOp = (string) $nfe->infNFe->ide->natOp;
            $serie = (string) $nfe->infNFe->ide->serie;
            $nProt = (string) !empty($std->protNFe->infProt->nProt) ? $std->protNFe->infProt->nProt : '';
            
            $nome = $emitRazao;
            if ($emitCNPJ == $cnpj) {
                $nome = $destRazao;
            }
            $email = !empty($nfe->infNFe->dest->email) 
                ? $nfe->infNFe->dest->email
                : '';
            $aObscont = !empty($std->NFe->infNFe->obsCont)
                ? $nfe->infNFe->obsCont
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
            $vICMS = (float) $nfe->infNFe->total->ICMSTot->vICMS;
            
            $valorFat = 0;
            $natOp1 = strtoupper(substr($nfe->infNFe->ide->natOp, 0, 1));
            if ($cStat == '100' || $cStat == '150') {
                $cobr = !empty($nfe->infNFe->cobr)
                    ? $nfe->infNFe->cobr
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
            }
            $pesoL = !empty($nfe->infNFe->transp->vol->pesoL)
               ? $nfe->infNFe->transp->vol->pesoL
               : 0;
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
