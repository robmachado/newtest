<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include_once '../bootstrap.php';

/**
 * Rotina de impressão da DANFE
 *
 * Esta rotina recebe como parâmetro o caminho para xml da NFe compactado e codificado em Base64
 *
 * @category   Application
 * @package    robmachado\teste
 * @copyright  Copyright (c) 2008-2015
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm at gmail dot com>
 * @link       http://github.com/robmachado/teste for the canonical source repository
 */

use NFePHP\DA\NFe\Danfe;


$xml = isset($_REQUEST['xml']) ? $_REQUEST['xml'] : '';
if ($xml == '') {
    exit();
}
$dxml = base64_decode($xml);
$xml = gzdecode($dxml);
$logo = !empty($_ENV['NFE_LOGO']) ? "images/".$_ENV['NFE_LOGO'] : "images/logo.jpg";
if (strpos($xml, 'recebidas')) {
    $logo = '';
}
$docxml = file_get_contents($xml);
$danfe = new Danfe($docxml, 'P', 'A4', $logo, 'I', '');
$id = $danfe->monta();
$pdf = $danfe->render();
header('Content-Type: application/pdf');
echo $pdf;

