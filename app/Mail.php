<?php
namespace App;

/**
 * Classe para preparar e enviar o email aos destinatarios das NFe
 * 
 * @category   Application
 * @package    robmachado\teste
 * @copyright  Copyright (c) 2008-2015
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm at gmail dot com>
 * @link       http://github.com/robmachado/teste for the canonical source repository
 */

use NFePHP\Mail\Mail as MailNFe;


class Mail
{
    public $error;
    public $mail;
    
    public function __construct()
    {
        $config = new \stdClass();
        $config->host = $_ENV['MAILHOST'];
        $config->user = $_ENV['MAILUSER'];
        $config->password = $_ENV['MAILPASS'];
        $config->secure = $_ENV['MAILPROTOCOL'];
        $config->port = $_ENV['MAILPORT'];
        $config->from = $_ENV['MAILFROM'];
        $config->fantasy = $_ENV['MAILFROMNAME'];
        $config->replyTo = $_ENV['MAILREPLY'];
        $config->replyName = $_ENV['MAILFROMNAME'];

        //a configuração é uma stdClass com os campos acima indicados
        //esse parametro é OBRIGATÓRIO
        $this->mail = new MailNFe($config);
    }
    
    /**
     * envia
     * rotina de envio do email com o xml da NFe
     * @param string $fileNfePath
     * @param string $addresses
     * @param boolean $comPdf
     * @param string $pathPdf
     * @return string
     */
    public function envia($xml, $addresses = '', $comPdf = false, $pdf = '')
    {
        try {
            $aPara = array();
            $addresses = str_replace(',', ';', $addresses);
            if (! is_array($addresses)) {
                $aPara = explode(';', $addresses);
            } else {
                $aPara = $addresses;
            }
            if (empty($pdf)) {
                $pdf = '';
                $comPdf = false;
            }
            
            $this->mail->loadDocuments($xml, $pdf);
    
            //envia emails
            $resp = $this->mail->send($aPara);
    
        } catch (\InvalidArgumentException $e) {
            echo "Falha: " . $e->getMessage();
        } catch (\RuntimeException $e) {
            echo "Falha: " . $e->getMessage();
        } catch (\Exception $e) {
            echo "Falha: " . $e->getMessage();
        }  
        return $resp;
    }
}
