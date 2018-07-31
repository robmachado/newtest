<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
include_once '../bootstrap.php';

use App\Process;

$link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

$filename = isset($_FILES['fileUpload']) ? $_FILES['fileUpload']['name'] : null;

$html = file_get_contents(APP_ROOT . '/publico/resources/layout.html');
$form = file_get_contents(APP_ROOT . '/publico/resources/formupload.php');
$message = [];

if (!empty($filename)) {
    $ext = strtolower(substr($filename, -4));
    $name = '';
    if ($ext == '.xml') {
        move_uploaded_file($_FILES['fileUpload']['tmp_name'], $_ENV['SIGNFOLDER'] . '/' . $filename);
        $message = [1, 'Arquivo recebido.'];
        try {
            $process = new Process();
            $process->send($filename);
        } catch (\Exception $e) {
            $message = [0, "ERROR: " . $e->getMessage()];
        }
    } else {
        $message = [2, 'Esse documento não é um xml.'];
        unlink($_FILES['fileUpload']['tmp_name']);
    }
}
$msg = '';
if (!empty($message)) {
    switch ($message[0]) {
        case 0:
            $msg = "<div class=\"alert alert alert-danger\" role=\"alert\" id=\"msg-alert\" data-auto-dismiss=\"2000\">"
                    . "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>"
                    . $message[1]
                    . "</div>";
            break;
        case 1:
            $msg = "<div class=\"alert alert-success\" role=\"alert\" id=\"msg-alert\" data-auto-dismiss=\"2000\">"
                    . "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>"
                    . $message[1]
                    . "</div>";
            break;
        case 2:
            $msg = "<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\" data-auto-dismiss=\"2000\" id=\"msg-alert\">"
                    . "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>"
                    . $message[1]
                    . "</div>";
            break;
    }
}

$script = "";
$html = str_replace('{{ alert }}', $msg, $html);
$html = str_replace('{{ script }}', $script, $html);
$html = str_replace('{{ content }}', $form, $html);

echo $html;
