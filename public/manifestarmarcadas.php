<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include_once '../bootstrap.php';

use App\DFe;

/**
 * Rotina para manifestar os resumos selecionados
 * 
 * @category   Application
 * @package    robmachado\teste
 * @copyright  Copyright (c) 2008-2015
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm at gmail dot com>
 * @link       http://github.com/robmachado/teste for the canonical source repository
 */

$lista = isset($_REQUEST['lista']) ? $_REQUEST['lista'] : '';
$aLista = explode(',', $lista);

$path = realpath($_ENV['RECEIVEDFOLDER']. '/resumo');
$dfe = new DFe();
$aInv = array_flip($aLista);
$resp = [];
foreach ($aLista as $res) {
    if (is_numeric($res)) {
        $resp[] = $dfe->manifesta($res);
    }    
}

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Manifestar</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css" href="css/teste.css">
    </head>
    <body>
    <div class="container">
        <h2>manifestar</h2>
        <?php
        if (!empty($resp)) {
            foreach ($resp as $res) {
                echo $res . '<br>';
            }
        }    
        ?>
    </div>
    </body>
</html>