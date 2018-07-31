<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/dfe_log.txt');
error_reporting(E_ALL);
include_once __DIR__ . '/../bootstrap.php';


/**
 * Rotina de busca das NFe destinadas, para ser usada via CRON com php-cli em modo console
 *
 * @category   Application
 * @package    robmachado\teste
 * @copyright  Copyright (c) 2018
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm at gmail dot com>
 * @link       http://github.com/robmachado/teste for the canonical source repository
 *
 */


if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);
}

use App\DFe;

$ini = date('Y-m-d H:i:s');
try {
    $dfe = new DFe();
    $dfe->getNFe(100);
} catch (\Exception $e) {
    echo $e->getMessage();
}
$fim = date('Y-m-d H:i:s');
$filename = __DIR__ . '/dfelog.log';
$conteudo = "DFe $ini --> $fim\n";
if (!is_file($filename)) {
    file_put_contents($filename, "\n");
}
if (is_writable($filename)) {
    if (!$handle = fopen($filename, 'a')) {
         echo "Não foi possível abrir o arquivo ($filename)";
         exit;
    }
    if (fwrite($handle, $conteudo) === FALSE) {
        echo "Não foi possível escrever no arquivo ($filename)";
        exit;
    }
    echo "Sucesso: Escrito ($conteudo) no arquivo ($filename)";
    fclose($handle);
} else {
    echo "O arquivo $filename não pode ser alterado";
}
exit;
