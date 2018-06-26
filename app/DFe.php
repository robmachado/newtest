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

use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;
use NFePHP\Common\DOMImproved;
use \DOMDocument;


class DFe
{
    public $tools;
    public $ultNSU = 0;
    public $maxNSU = 0;
    public $nsu;
    public $pathNFe;
    public $pathRes;
    public $pathEvt;
    public $st;
    
    public function __construct()
    {
        $certificate = Certificate::readPfx(file_get_contents(APP_ROOT.'/certs/'.$_ENV['CERTIFICATE']), $_ENV['PASSWORD']);
        $config = file_get_contents('../config/config.json');
        $this->tools = new Tools($config, $certificate);
        $this->tools->model('55');
        $this->pathNFe = $_ENV['NFEFOLDER'];
        $this->pathEvt = $_ENV['EVENTFOLDER'];
        $this->pathRec = $_ENV['RECEIVEDFOLDER'];
        $this->nsu = APP_ROOT.'/base/nsu.json';
        $this->getNSU();
        $this->st = new Standardize();
    }
    
    /**
     * getNSU
     * 
     * Carrega os numeros do ultNSU e de maxNSU 
     * gravados no arquivo da pasta base
     * Esses numeros são usados para continuação das buscas
     * no webservice de forma a trazer apenas os ultimos documentos
     * ainda não importados
     */
    public function getNSU()
    {
        if (! is_file($this->nsu)) {
            $aNSU = array('ultNSU' => 0, 'maxNSU' => 0);
            $nsuJson = json_encode($aNSU);
            file_put_contents($this->nsu, $nsuJson);
        }
        $nsuJson = json_decode(file_get_contents($this->nsu));
        $this->ultNSU = (int) $nsuJson->ultNSU;
        $this->maxNSU = (int) $nsuJson->maxNSU;
    }
    
    /**
     * putNSU
     * Grava os numeros de ultNSU e maxNSU em um arquivo em formato json
     * para serem utilizados posteriormente em outras buscas
     * 
     * @param integer $ultNSU
     * @param integer $maxNSU
     */
    public function putNSU($ultNSU = 0, $maxNSU = 0)
    {
        //o valor perc é destinado a ser usado com um "progress bar"
        //em uma solicitação manual do usuário via página web
        $perc = 0;
        if ($maxNSU > 0) {
            $perc = round(($ultNSU/$maxNSU)*100, 0);
        }
        $aNSU = array('ultNSU' => $ultNSU, 'maxNSU' => $maxNSU, 'perc' => $perc);
        $nsuJson = json_encode($aNSU);
        file_put_contents($this->nsu, $nsuJson);
    }
    
    /**
     * getNFe
     * Usa o webservice DistDFe da SEFAZ AN para trazer os docuentos destinados ao
     * CNPJ do config.json e salvar as NFe retornadas na pasta recebidas/<anomes>
     * 
     * @param int $limit
     */
    public function getNFe($limit = 10)
    {
        $nsuproc=0;
        if ($this->ultNSU == $this->maxNSU) {
            $this->maxNSU++;
        }
        if ($limit > 100 || $limit == 0) {
            $limit = 10;
        }
        $iCount = 0;
        while ($this->ultNSU < $this->maxNSU) {
            $iCount++;
            if ($iCount > ($limit - 1)) {
                break;
            }
            try {
                //limpar a variavel de retorno
                $resp = $this->tools->sefazDistDFe($this->ultNSU);
            } catch (\Exception $e) {
                echo $e->getMessage();
                die;
            }
            $dom = new \DOMDocument();
            $dom->loadXML($resp);
            $node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
            $tpAmb = $node->getElementsByTagName('tpAmb')->item(0)->nodeValue;
            $verAplic = $node->getElementsByTagName('verAplic')->item(0)->nodeValue;
            $cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
            $xMotivo = $node->getElementsByTagName('xMotivo')->item(0)->nodeValue;
            $dhResp = $node->getElementsByTagName('dhResp')->item(0)->nodeValue;
            $this->ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
            $this->maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
            $lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);
            if (empty($lote)) {
                continue;
            }
            $docs = $lote->getElementsByTagName('docZip');
            $d = [];
            foreach ($docs as $doc) {
                $numnsu = $doc->getAttribute('NSU');
                $schema = $doc->getAttribute('schema');
                $content = gzdecode(base64_decode($doc->nodeValue));
                $tipo = substr($schema, 0, 6);
                $processo = "p$tipo";
                $std = $this->st->toStd($content);
                //processa o conteudo do NSU
                $this->$processo($std, $content);
                $nsuproc++;
            }
            sleep(2);
            $this->putNSU($this->ultNSU, $this->maxNSU);
        }
    }
    
    protected function pprocNF(\stdClass $std, $content)
    {
        $dt = new \DateTime($std->NFe->infNFe->ide->dhEmi);
        $anomes = $dt->format('Ym');
        $chNFe = preg_replace('/[^0-9]/', '', $std->NFe->infNFe->attributes->Id);
        if (! is_dir($this->pathNFe."/$anomes/")) {
            mkdir($this->pathNFe."/$anomes/");
        } 
        return file_put_contents(
            $this->pathNFe."/$anomes/$chNFe-nfe.xml",
            $content
        );
    }
    
    /**
     * Processa Resumos de Eventos como emissão de CTe
     * @param DOMDocument $dom
     * @return none
     */
    protected function presEve(\stdClass $std, $content)
    {
        $dt = new \DateTime($std->dhRecbto);
        $chNFe = $std->chNFe;
        $tpEvento = $std->tpEvento;
        $nSeqEvento = $std->nSeqEvento;
        $anomes = $dt->format('Ym');
        if (! is_dir($this->pathEvt."/$anomes/")) {
            mkdir($this->pathEvt."/$anomes/");
        } 
        return file_put_contents(
            $this->pathEvt."/$anomes/$chNFe-$tpEvento-reseve.xml",
            $content     
        );
    }
    
    protected function pprocEv(\stdClass $std, $content)
    {
        $dt = new \DateTime($std->evento->infEvento->dhEvento);
        $chNFe = $std->retEvento->infEvento->chNFe;
        $tpEvento = $std->retEvento->infEvento->tpEvento;
        $nSeqEvento = $std->retEvento->infEvento->nSeqEvento;
        $anomes = $dt->format('Ym');
        if ($tpEvento == '110111' ) {
            //é cancelamento
            $nfam = '20' . substr($chNFe, 2, 4);
            $file = $this->pathRec . "/$nfam/$chNFe-nfe.xml";
            $this->markCancel($file);
        }
        if (! is_dir($this->pathEvt."/$anomes/")) {
            mkdir($this->pathEvt."/$anomes/");
        } 
        return file_put_contents(
            $this->pathEvt."/$anomes/$chNFe-$tpEvento-evt.xml",
            $content     
        );
    }
    
    protected function presNFe(\stdClass $std, $content)
    {
        $chNFe = $std->chNFe;
        return file_put_contents(
            $this->pathRec . "/resumo/$chNFe-resNFe.xml",
            $content     
        );
    }
    
    /**
     * manifesta
     * @param string $chNFe
     */
    public function manifesta($chNFe)
    {
        $resp = $this->tools->sefazManifesta($chNFe, '210210');
        $std = $this->st->toStd($resp);
        if ($std->cStat == '128') {
            $cStat = $std->retEvento->infEvento->cStat;
            $xMotivo= $std->retEvento->infEvento->xMotivo;
            $retorno = "$chNFe [$cStat] - $xMotivo";
            if ($cStat == 135 || $cStat == 573 || $cStat == 650) {
                $path = $this->pathRec."/resumo/$chNFe-resNFe.xml";
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
        return $retorno;
    }
    
    /**
     * Edita a NFe recebida de terceiros indicando o cancelamento
     * @param string $pathFile
     */
    private static function markCancel($pathFile)
    {
        if (is_file($pathFile)) {
            $nfe = new \DOMDocument();
            $nfe->load($pathFile);
            $infProt = $nfe->getElementsByTagName('infProt')->item(0);
            $infProt->getElementsByTagName('cStat')->item(0)->nodeValue = '101';
            $nfe->save($pathFile);
        }
    }
}
