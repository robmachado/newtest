<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include_once '../bootstrap.php';

/**
 * Rotina de envio do emails com a NFe
 * Esta rotina recebe como parâmetro :
 * 
 * chave --> chave da NFe
 * xml --> o xml da NFe compactado e codificado em Base64
 * para --> string com os endereços de email dos destinatários separados com ';'
 * comPdf --> integer 1 ou nada para indicar que deve ser renderizado um DANFE para 
 * ser enviado anexado ao email
 * 
 * @category   Application
 * @package    robmachado\teste
 * @copyright  Copyright (c) 2008-2015
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm at gmail dot com>
 * @link       http://github.com/robmachado/teste for the canonical source repository
 */

use App\Mail;
use NFePHP\DA\NFe\Danfe;

$chave = isset($_POST['chave']) ? $_POST['chave'] : '';
$xml = isset($_POST['xml']) ? $_POST['xml'] : '';
$para = isset($_POST['para']) ? $_POST['para'] : '';
$comPdf = isset($_POST['comPdf']) ? $_POST['comPdf'] : '0';

$dxml = base64_decode($xml);
$xml = gzdecode($dxml);

$pathPdf = '';
$bPdf = false;

if ($comPdf === '1') {
    $bPdf = true;
    $logo = !empty($_ENV['NFE_LOGO']) ? "images/".$_ENV['NFE_LOGO'] : "";
    if (strpos($xml, 'recebidas')) {
        $logo = '';
    }
    $docxml = file_get_contents($xml);
    $danfe = new Danfe($docxml, 'P', 'A4', $logo, 'I', '');
    $id = $danfe->monta();
    $pdf = $danfe->render();
}

$mail = new Mail();
$resp = $mail->envia($docxml, $para, $bPdf, $pdf);
if ($resp === true) {
    echo "SUCESSO NFe n. $chave, enviada para $para.";
} else {
    echo "FRACASSO!! houve algum problema. $mail->error";
}

