<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

include_once '../bootstrap.php';

/**
 * Rotina de entrada da aplicação, apresenta uma lista das NFe 
 * gravatas na pasta indicada
 * 
 * Esta rotina recebe como parâmetro :
 *  pasta -- que tipo de NFe deve ser buscada ENTRADAS, APROVADAS ou RECEBIDAS
 *  ano -- ano a ser listado
 *  mes -- mês a ser listado
 * 
 * @category   Application
 * @package    robmachado\teste
 * @copyright  Copyright (c) 2008-2015
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm at gmail dot com>
 * @link       http://github.com/robmachado/teste for the canonical source repository
 */

use App\Status;
use App\Dates;
use App\Dados;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__FILE__)));
}

//carrega os dados de configuração
$cfile = APP_ROOT .'/config/config.json';
$configJson = file_get_contents($cfile);
$objConfig = json_decode($configJson);
//estabelece o ambiente
$ambiente = 'homologacao';
if ($objConfig->tpAmb == '1') {
    $ambiente = 'producao';
}
//verifica o status
$htmlStatus = Status::verifica($configJson);
//verifica certificado
$htmlCert = Status::getExpirDate();
//cria uma lista vazia

$aList = array();
//carrega as entradas em post ou get
$pasta = isset($_REQUEST['pasta']) ? $_REQUEST['pasta'] : '';
$ano = isset($_REQUEST['ano']) ? $_REQUEST['ano'] : '';
$mes = isset($_REQUEST['mes']) ? $_REQUEST['mes'] : '';

if ($pasta == '') {
    $pasta = 'APROVADAS';
}

if ($ano == '') {
    $ano = date('Y');
}
if ($mes == '') {
    $mes = date('m');
}

//cria lista de anos desde 2008 que foi o incio dos dados
$anoAtual = date('Y');
$selAnos = "<select size=\"1\" name=\"ano\" id=\"ano\">\n";
for ($xCon = 2008; $xCon <= $anoAtual; $xCon++) {
    $sel = '';
    if ($xCon == $ano) {
        $sel = 'SELECTED ';
    }
    $selAnos .= "<option ".$sel."value=\"$xCon\">$xCon</option>\n";
}
$selAnos .= "</select>\n";
//cria lista de meses
$selMeses = "<select size=\"1\" name=\"mes\" id=\"mes\">\n";
for ($xCon = 1; $xCon <= 12; $xCon++) {
    $sel = '';
    $txtMes = str_pad($xCon, 2, "0", STR_PAD_LEFT);
    if ($xCon == (int) $mes) {
        $sel = 'SELECTED ';
    }
    $selMeses .= "<option ".$sel."value=\"$txtMes\">$txtMes</option>\n";
}
$selMeses .= "</select>\n";
$chkAprovadas = '';
$chkEnviadas = '';
$chkRecebidas = '';
$aDados = array(
    'totFat' => 0,
    'totPeso' => 0,
    'totIcms' => 0,
    'aNF' => array()
);
//caso tenha sido passados os dados
$dias = Dates::diasUteis(date('m'), date('Y'));
$hoje = date('d/m/Y');
$titulo = "Notas Fiscais";
$htmlMsgPasta = "<i>[hoje $hoje com $dias dias úteis no mês.]</i>";
if (!empty($pasta) && !empty($ano) && !empty($mes)) {
    if ($pasta == 'APROVADAS') {
        $caminho = 'enviadas'.DIRECTORY_SEPARATOR.'aprovadas'.DIRECTORY_SEPARATOR.$ano.$mes;
        $chkAprovadas = 'SELECTED ';
    } elseif ($pasta == 'ENVIADAS') {
        $caminho = 'enviadas'.DIRECTORY_SEPARATOR;
        $chkEnviadas = 'SELECTED ';
    } else {
        $caminho = 'recebidas'.DIRECTORY_SEPARATOR.$ano.$mes;
        $chkRecebidas = 'SELECTED ';
    }
    $aList = array();
    $mensagem = '';
    $titulo .= " $mes/$ano";
    $path = realpath($_ENV['NFEFOLDER'].'/../../').'/'.$caminho;
    try {
        $aList = glob($path.'/*.xml');
    } catch (InvalidArgumentException $exc) {
        $mensagem = $exc->getMessage();
    }
    $aDados = Dados::extrai($aList, $objConfig->cnpj);
    $numNF = count($aDados['aNF']);
    $numCanc = Dados::$nCanc;
    $dias = Dates::diasUteis($mes, $ano);
    $media = round($numNF/$dias, 0);

    $htmlMsgPasta = "<i>Total de $numNF notas no mês $mes. "
            . "{ $numCanc notas canceladas} [ $media NFe/dia (até hoje $hoje) "
            . "e $dias dias úteis no mês.]</i>";


}
$selPasta = "<select size=\"1\" name=\"pasta\" id=\"pasta\">
    <option ".$chkEnviadas."value=\"ENVIADAS\">ENVIADAS</option>
    <option ".$chkAprovadas."value=\"APROVADAS\">APROVADAS</option>
    <option ".$chkRecebidas."value=\"RECEBIDAS\">RECEBIDAS</option>
</select>";

$htmlNotas = "";
$i = 0;
$totFatSP = 0;
$totFatOutros = 0;
$totICMSSP = 0;
$totICMSOutros = 0;
$totFat = number_format($aDados['totFat'], '2', ',', '.');
$totFatProd = number_format($aDados['totFatProd'], '2', ',', '.');
$totFatServ = number_format($aDados['totFatServ'], '2', ',', '.');
$totIcms = number_format($aDados['totIcms'], '2', ',', '.');
$totPesoProd = number_format($aDados['totPesoProd'], '2', ',', '.');
$totPesoServ = number_format($aDados['totPesoServ'], '2', ',', '.');
if ($aDados['totPesoProd'] > 0) {
    $pmedProd = number_format($aDados['totFatProd']/$aDados['totPesoProd'], '2', ',', '.');
} else {
    $pmedProd = '0';
}    
if ($aDados['totPesoServ'] > 0) {
    $pmedServ = number_format($aDados['totFatServ']/$aDados['totPesoServ'], '2', ',', '.');
} else {
    $pmedServ = 0;
}    

$fatMedio = round($aDados['totFat']/$dias);
$fatMedioTxt = number_format($fatMedio, '2', ',', '.');
$fatProj = number_format($aDados['totFat'], '2', ',', '.');
if ($ano.$mes == date('Ym')) {
    $diasRest = $dias - Dates::diasUteisNow($mes, $ano);
    $fatProj = number_format($aDados['totFat'] + ($fatMedio * $diasRest), '2', ',', '.');
}
if (count($aDados['aNF']) > 0) {
    foreach ($aDados['aNF'] as $dado) {
        $gzPath = base64_encode(gzencode($aList[$i]));
        $pathDanfe = "./print.php?xml=".$gzPath;
        $address = base64_encode(gzencode($dado['email']));
        $chave = $dado['nNF'];
        $clickMail = "<a href=\"#\" onClick=\"mailDanfe('$chave', '$gzPath','$address');\">".$dado['nome']."</a>";
        $htmlLinhaNota = "<tr class=\"dados\">\n";
        if ($dado['cStat'] == '') {
            $clickMail = "<a href=\"#\" onClick=\"alert('Nota não protocolada - o email não pode ser enviado!');\">".$dado['nome']."</a>";
            $htmlLinhaNota = "<tr class=\"fail\">\n";
        } elseif ($dado['cStat'] != '100') {
            $clickMail = "<a href=\"#\" onClick=\"alert('Nota Cancelada - o email não pode ser enviado!');\">".$dado['nome']."</a>";
            $htmlLinhaNota = "<tr class=\"cancel\">\n";
        }
        $peso = !empty($dado['peso']) ? number_format($dado['peso'],1, ',' ,'.').' kg' : '';
        $valor = !empty($dado['valorFat']) ? 'R$ '.number_format($dado['valorFat'], 2, ',', '.') : '';
        $htmlLinhaNota .= "<td class=\"center\"><a href=\"#\" onClick=\"printDanfe('$gzPath');\">".$dado['nNF']."</a></td>
            <td class=\"center\">".$dado['serie']."</td>
            <td class=\"center\">".$dado['data']."</td>
            <td class=\"left email\">$clickMail</td>
            <td class=\"right\" width=\"10%\">".$dado['vNF']."</td>
            <td class=\"right\">".$valor."</td>
            <td class=\"right\">".$peso."</td>
            <td class=\"left\"><a href=\"#\" onClick=\"openXml('$gzPath');\">".$dado['natureza']."</a></td>
            </tr>\n";
        $htmlNotas .= $htmlLinhaNota;
        $vFat = !empty($dado['valorFat']) ? $dado['valorFat'] : 0;
        //$vFat = str_replace(['R','$'], '', $dado['valorFat']);
        //$vFat = str_replace('.', '', $vFat);
        //$vFat = trim(str_replace(',', '.', $vFat));
        if ($dado['uf'] == 'SP') {
            $totICMSSP += $dado['icms'];
            $totFatSP += $vFat;
        } else {
            $totICMSOutros += $dado['icms'];
            $totFatOutros += $vFat;
        }
        $i++;
    }
}

$strICMSSP = number_format($totICMSSP, 2, ',', '.'); 
$strICMSOutros = number_format($totICMSOutros, 2, ',', '.');
if (($totFatOutros+$totFatSP) > 0) {
    $strRazaoICMS = number_format(($totFatOutros/($totFatOutros+$totFatSP))*100, 2, ',', '.');      
    $strICMSpagar = number_format($totICMSOutros, 2, ',', '.') . " - totalICMScredito x " . number_format($totFatOutros/($totFatOutros+$totFatSP), 3, ',', '.'); 
} else {
    $strRazaoICMS  = '';
    $strICMSpagar = '';
}

$html = "<!DOCTYPE html>
<html>
    <head>
        <title>Notas Fiscais</title>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <script src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js\"></script>
        <script src=\"resources/stupidtable.js?dev\"></script>
        <link rel=\"stylesheet\" type=\"text/css\" href=\"css/teste.css\">
    </head>
    <body>
    <script>
        $(function() {
            //Helper function para valores formatados em R$
            var valor_from_string = function(str) {
                var newstr = str.replace(/(\\t*) */g, '');
                newstr = newstr.replace(/[A-Z]/g, '');
                newstr = newstr.replace(/[$]/g, '');
                newstr = newstr.replace(/\./g, '');
                newstr = newstr.replace(/[,]/g, '.');
                var valor = parseInt(newstr)*100;
                return valor;
            }
            var table = $(\"table\").stupidtable({
                \"valor\": function(a,b) {
                    // Get these into int objects for comparison.
                    aVal = valor_from_string(a);
                    bVal = valor_from_string(b);
                    return aVal - bVal;
                }
            });
            table.on(\"beforetablesort\", function (event, data) {
                // Apply a \"disabled\" look to the table while sorting.
                // Using addClass for testing as it takes slightly longer to render.
                $(\"#msg\").text(\"Organizando a tabela ...\");
                $(\"table\").addClass(\"disabled\");
            });
            table.on(\"aftertablesort\", function (event, data) {
                // Reset loading message.
                $(\"#msg\").html(\"&nbsp;\");
                $(\"table\").removeClass(\"disabled\");
                var th = $(this).find(\"th\");
                th.find(\".arrow\").remove();
                var dir = $.fn.stupidtable.dir;
                var arrow = data.direction === dir.ASC ? \"&uarr;\" : \"&darr;\";
                th.eq(data.column).append('<span class=\"arrow\">' + arrow +'</span>');
            });
        });    
    </script>
    <script>
        function OpenWindowWithPost(url, windowoption, name, params) {
            var form = document.createElement(\"form\");
            form.setAttribute(\"method\", \"post\");
            form.setAttribute(\"action\", url);
            form.setAttribute(\"target\", name);
             for (var i in params) {
                if (params.hasOwnProperty(i)) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = i;
                    input.value = params[i];
                    form.appendChild(input);
                }
            }
            document.body.appendChild(form);
            window.open(\"\", name, windowoption);
            form.submit();
            document.body.removeChild(form);
        }
        function openXml(dest) {
            var url = 'openxml.php';
            var name = 'page';
            var param = { 'xml' : dest };
            var specs = 'scrollbars=no,menubar=no,height=600,width=800,resizable=yes,toolbar=no,status=no';
            OpenWindowWithPost(url, specs, name, param);
        }
        function printDanfe(dest) {
            var url = 'print.php';
            var name = 'page';
            var specs = 'scrollbars=no,menubar=no,height=600,width=800,resizable=yes,toolbar=no,status=no';
            var param = { 'xml' : dest };
            OpenWindowWithPost(url, specs, name, param);
        }
        function mailDanfe(chave, dest, address) {
            var url = 'email.php';
            var name = 'page';
            var specs = 'scrollbars=no,menubar=no,height=160,width=650,resizable=yes,toolbar=no,status=no';
            var param = { 'chave' : chave, 'xml' : dest, 'address' : address };
            OpenWindowWithPost(url, specs, name, param);		
        }
        function openmanifestar() {
            window.open(\"manifestar.php\",\"_self\",false);
            //var url = 'manifestar.php';
            //var name = 'page';
            //var specs = 'scrollbars=yes,menubar=yes,resizable=yes,toolbar=yes,status=yes';
            //var param = {};
            //OpenWindowWithPost(url, specs, name, param);
        }
    </script>
        <div class=\"container\">
            <div class=\"left\"><img src=\"images/logo.jpg\" alt=\"logo\" height=\"62\"></div>
            <div class=\"left\">
                <h1 align=\"center\">$titulo</h1>
                $htmlStatus
                $htmlCert
                <p id=\"msg\">&nbsp;</p>    
            </div>
            <div class=\"right\">
                <form method=\"POST\" name=\"myform\" action=\"\">
                    <label>Origem</label><br>$selPasta<br>
                    <div>
                    <label>Ano</label><br>$selAnos
                    </div>
                    <div>
                    <label>Mês</label><br>$selMeses
                    </div>
                    <br>
                    <input type=\"submit\" value=\"Buscar as Notas\" name=\"B1\"><input type=\"button\" value=\"Listar Resumos das NFe's\" name=\"B2\" onClick=\"openmanifestar();\">
                </form>
            </div>
        </div>
        <div class=\"container\">
            <table width=\"95%\">
                <tr>
                    <td>$htmlMsgPasta</td>
                </tr>
            </table>
            <table width=\"95%\">
                <thead>
                <tr>
                    <th class=\"border\" data-sort=\"int\">Número</th>
                    <th class=\"border\" data-sort=\"int\">Série</th>
                    <th class=\"border\" data-sort=\"string\">Data</th>
                    <th class=\"border\" data-sort=\"string\">Destinatário/Emitente</th>
                    <th class=\"border\" data-sort=\"valor\" width=\"10%\">Valor</th>
                    <th class=\"border\" data-sort=\"valor\">Fatura</th>
                    <th class=\"border\" data-sort=\"valor\">Peso Liq</th>
                    <th class=\"border\" data-sort=\"string\">Natureza da Operação</th>
                </tr>
                </thead>
                <tbody>
                $htmlNotas
                </tbody>
            </table>
        </div>
        <div class=\"container\">
            <center>
            <h2>$mensagem</h2>
            <table border=\"0\" cellspacing=\"1\" width=\"40%\">
                <tr>
                    <td class=\"right\">Total Faturado</td>
                    <td class=\"right\">R$ $totFat</td>
                </tr>
                <tr>
                    <td class=\"right\">Total Venda Faturado</td>
                    <td class=\"right\">R$ $totFatProd</td>
                </tr>
                <tr>
                    <td class=\"right\">Peso Liquido Venda Faturado</td>
                    <td class=\"right\">$totPesoProd kg</td>
                </tr>
                <tr>
                    <td class=\"right\">Preço Médio Venda</td>
                    <td class=\"right\">R$ $pmedProd /kg</td>
                </tr>
                <tr>
                    <td class=\"right\">Total Serviço Faturado</td>
                    <td class=\"right\">R$ $totFatServ</td>
                </tr>
                <tr>
                    <td class=\"right\">Peso Liquido Serviço Faturado</td>
                    <td class=\"right\">$totPesoServ kg</td>
                </tr>
                <tr>
                    <td class=\"right\">Preço Médio Serviço</td>
                    <td class=\"right\">R$ $pmedServ /kg</td>
                </tr>
                <tr>	
                    <td class=\"right\">Total ICMS</td>
                    <td class=\"right\">R$ $totIcms</td>
                </tr>
                <tr>
                    <td class=\"right\">Fat. Médio Diário</td>
                    <td class=\"right\">R$ $fatMedioTxt</td>
                </tr>
                <tr>
                    <td class=\"right\"><i>Fat. Projetado</i></td>
                    <td class=\"right\"><i>R$ $fatProj</i></td>
                </tr>
                 <tr>
                    <td class=\"right\"><i>ICMS Interno</i></td>
                    <td class=\"right\"><i>R$ $strICMSSP</i></td>
                </tr>
                <tr>
                    <td class=\"right\"><i>ICMS Interestadual</i></td>
                    <td class=\"right\"><i>R$ $strICMSOutros</i></td>
                </tr>
                <tr>
                    <td class=\"right\"><i>Razão</i></td>
                    <td class=\"right\"><i>$strRazaoICMS %</i></td>
                </tr>
                <tr>
                    <td class=\"right\"><i>ICMS Pagar</i></td>
                    <td class=\"right\"><i>$strICMSpagar</i></td>
                </tr>
            </table>
            </center>
        </div>
        
    </body>
</html>
";

echo $html;
