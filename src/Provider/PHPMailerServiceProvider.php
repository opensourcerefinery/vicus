<?php

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/. */

 namespace Vicus\Provider;

 /**
  * Description of DatabaseManagerProvider
  *
  * @author Michael Koert <mkoert at bluebikeproductions.com>
  */

 use Vicus\Api\BootableProviderInterface;
 use Pimple\Container;
 use Pimple\ServiceProviderInterface;

// include "vendor/phpmailer/phpmailer/PHPMailerAutoload.php";

class PHPMailerServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $container)
    {
    	$container['mail'] = function($c) {
    		//Create a new PHPMailer instance
            $mail = new PHPMailer();

            //Tell PHPMailer to use SMTP
            // if($c['phpmailer']['smtp'])
            $mail->isSMTP();

            //Enable SMTP debugging
            // 0 = off (for production use)
            // 1 = client messages
            // 2 = client and server messages

            $mail->SMTPDebug = $c['phpmailer']['smtpdebug'];

            //Ask for HTML-friendly debug output
            $mail->Debugoutput = 'html';

            //Set the hostname of the mail server
            $mail->Host = $c['phpmailer']['host'];

            //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
            $mail->Port = $c['phpmailer']['port'];

            //Set the encryption system to use - ssl (deprecated) or tls
            $mail->SMTPSecure = $c['phpmailer']['smtpsecure'];

            //Whether to use SMTP authentication
            $mail->SMTPAuth = $c['phpmailer']['smtpauth'];

            //Username to use for SMTP authentication - use full email address for gmail
            $mail->Username = $c['phpmailer']['username'];

            //Password to use for SMTP authentication
            $mail->Password = $c['phpmailer']['password'];

            //Set who the message is to be sent from
            // $mail->setFrom($c['phpmailer.mail'], $c['phpmailer.firm']);

    		return $mail;

        };
    }

    public function boot(Container $container)
    {

    }
}
