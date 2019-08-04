<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 8/1/2017
 * Time: 3:21 PM
 */

namespace App\Managers;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Slim\Container;
use Slim\Router;

class Mail
{
    /**
     * @var string - DEFAULT FROM Address (getting auto from .env)
     */
    private static $DEFAULT_FROM;

    /**
     * HOST
     */
    private static $HOSTNAME;

    /**
     * @var Mail $instance
     */
    private static $instance;

    /**
     * MAILER INSTANCE
     */
    public $mail;

    /**
     * @var Router $router
     */
    private static $router;

    /**
     * Returns Mail instance
     *
     * @return Mail
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * setup the Mail Class
     * @param Container $container
     */
    public static function initialize($container)
    {
        static::$router = $container->router;

        $uri = $container->request->getUri();

        static::$HOSTNAME = $uri->getBaseUrl();

        static::$DEFAULT_FROM = getenv('MAIL_FROM_EMAIL');
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * User instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Mail constructor
     */
    protected function __construct()
    {
        //$this->mail = new \PHPMailer();

        $this->mail = new PHPMailer(true);

        $is_smtp = getenv('IS_SMTP');

        if ($is_smtp === "true") {
            $this->mail->isSMTP();
            $this->mail->Host = getenv('MAIL_HOST');

            if (getenv('MAIL_AUTH') != "false") {
                $this->mail->SMTPAuth = true;
                $this->mail->Username = getenv('MAIL_USER_NAME');
                $this->mail->Password = getenv('MAIL_PASSWORD');
            } else {
                $this->mail->SMTPAuth = false;
            }

            if (getenv('MAIL_SMTPSecure') != "false") {
                $this->mail->SMTPSecure = getenv('MAIL_SMTPSecure');
            } else {
                /*Specific TLS configurations
                $this->mail->SMTPSecure = false;
                $this->mail->SMTPAutoTLS = false;
                $this->mail->SMTPOptions = array(
                    'ssl'         => array(
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ),
                );
                */
            }

            $this->mail->SMTPDebug = 0;
            if (getenv('MAIL_DEBUG')) {
                $this->mail->SMTPDebug = getenv('MAIL_DEBUG');
            }

            $this->mail->Port = getenv('MAIL_PORT');
        }

        $this->mail->CharSet = 'UTF-8';
    }

    private function reset()
    {
        $this->mail->clearAddresses();
        $this->mail->clearAttachments();
    }

    private function send()
    {
        try {

            if ($this->mail->send()) {
                return true;
            }

        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    public function sendTestMail()
    {
        $this->mail->setFrom('from@example.com', 'Mailer');
        $this->mail->addAddress('dnatzkin@voicecom.bg', 'Joe User');     // Add a recipient
        //$this->mail->addReplyTo('info@example.com', 'Information');
        //$this->mail->addCC('cc@example.com');
        //$this->mail->addBCC('bcc@example.com');

        //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $this->mail->isHTML(true);                                  // Set email format to HTML

        $this->mail->Subject = 'Here is the subject';
        $this->mail->Body = 'This is the HTML message body <b>in bold!</b>';
        $this->mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        if (!$this->mail->send()) {
            throw new \Exception('Message could not be sent. Mailer Error: ' . $this->mail->ErrorInfo);
        } else {
            throw new \Exception('Message has been sent');
        }
    }

    /**
     * @param string $to - email
     * @param string $names
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public function sendWelcomeMail($to, $names, $title, $subject, $body)
    {
        $this->reset();

        $this->mail->setFrom(static::$DEFAULT_FROM, getenv('MAIL_FROM_EMAIL'));
        $this->mail->addAddress($to, $names);

        $this->mail->isHTML(true);

        $this->mail->Subject = $subject;

        $body_template = file_get_contents(MAIL_TEMPLATES_FOLDER . 'basic_template.html');
        $body_template = str_replace('|*TITLE*|', $title, $body_template);
        $body_template = str_replace('|*BODY*|', $body, $body_template);
        $body_template = str_replace('*|MC_PREVIEW_TEXT|*', $title, $body_template);


        $this->mail->msgHTML($body_template);

        if ($this->send()) {
            return true;
        }

        return false;
    }

    /**
     * SEND PDF TO CLIENT
     *
     * @param string $to
     * @param string $names
     * @param string $file_url
     * @param $subject
     * @param $title
     * @param $body
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function sendPdfReport($to, $names, $file_url, $subject, $title, $body, $filename)
    {
        $this->reset();

        $this->mail->setFrom(static::$DEFAULT_FROM, getenv('MAIL_FROM_EMAIL'));
        $this->mail->addAddress($to, $names);

        $this->mail->isHTML(true);

        $this->mail->Subject = $subject;

        $body_template = file_get_contents(MAIL_TEMPLATES_FOLDER . 'basic_template.html');
        $body_template = str_replace('|*TITLE*|', $title, $body_template);
        $body_template = str_replace('|*BODY*|', $body, $body_template);
        $body_template = str_replace('*|MC_PREVIEW_TEXT|*', $title, $body_template);

        $this->mail->addAttachment($file_url, $filename, 'base64', 'application/pdf');

        $this->mail->msgHTML($body_template);

        if ($this->send()) {
            return true;
        }

        return false;
    }

    /**
     * @param string $to
     * @param string $names
     * @param string $subject
     * @param string $body
     * @param array $files
     * @return bool
     * @throws Exception
     */
    public function sendNotificationMail($to, $names, $subject, $body, $files)
    {
        $this->reset();

        $this->mail->Encoding = 'base64';
        $this->mail->setFrom(static::$DEFAULT_FROM, getenv('MAIL_FROM_EMAIL'));
        $this->mail->addAddress($to, $names);

        $this->mail->isHTML(true);

        $this->mail->Subject = $subject;

        foreach ($files as $value) {
            $this->mail->addAttachment(
                NOTIFICATION_FILE_FOLDER . '/' . $value['uid'] . '.' . $value['extension'],
                $value['filename'] . '.' . $value['extension'],
                'base64',
                $value['mimetype']
            );
        }

        $this->mail->msgHTML($body);

        if ($this->send()) {
            return true;
        }

        return false;
    }

    /**
     * @param string $to
     * @param string $names
     * @param string $subject
     * @param string $body
     * @param string $files
     * @return bool
     */
    public function sendTemplateMail($to, $names, $subject, $body, $files)
    {
        $this->reset();

        $this->mail->Encoding = 'base64';
        $this->mail->setFrom(static::$DEFAULT_FROM, getenv('MAIL_FROM_EMAIL'));
        $this->mail->addAddress($to, $names);

        $this->mail->isHTML(true);

        $this->mail->Subject = $subject;

        $this->mail->addAttachment($files, basename($files), 'base64', 'application/pdf');

        $this->mail->msgHTML($body);

        if ($this->send()) {
            return true;
        }

        return false;
    }

    /**
     * @param string $to
     * @param string $names
     * @param string $subject
     * @param string $title
     * @param string $body
     * @return bool
     */
    public function sendDocumentNotification($to, $names, $subject, $title, $body)
    {
        $this->reset();

        $this->mail->Encoding = 'base64';
        $this->mail->setFrom(static::$DEFAULT_FROM, getenv('MAIL_FROM_EMAIL'));
        $this->mail->addAddress($to, $names);

        $this->mail->isHTML(true);

        $this->mail->Subject = $subject;

        $template_mail = file_get_contents(MAIL_TEMPLATES_FOLDER . 'basic_template.html');
        $template_mail = str_replace('|*BODY*|', $body, $template_mail);
        $template_mail = str_replace('|*TITLE*|', $title, $template_mail);
        $template_mail = str_replace('*|MC_PREVIEW_TEXT|*', $title, $template_mail);

        $this->mail->msgHTML($template_mail);

        if ($this->send()) {
            return true;
        }

        return false;
    }

    /**
     * @param string $to
     * @param string $names
     * @param string $subject
     * @param string $title
     * @param string $body
     * @return bool
     */
    public function sendErrorMail($to, $names, $subject, $title, $body)
    {
        $this->reset();

        $this->mail->Encoding = 'base64';
        $this->mail->setFrom(static::$DEFAULT_FROM, getenv('MAIL_FROM_EMAIL'));
        $this->mail->addAddress($to, $names);

        $this->mail->isHTML(true);

        $this->mail->Subject = $subject;

        $template_mail = file_get_contents(MAIL_TEMPLATES_FOLDER . 'basic_template.html');
        $template_mail = str_replace('|*BODY*|', $body, $template_mail);
        $template_mail = str_replace('|*TITLE*|', $title, $template_mail);
        $template_mail = str_replace('*|MC_PREVIEW_TEXT|*', $title, $template_mail);

        $this->mail->msgHTML($template_mail);

        if ($this->send()) {
            return true;
        }

        return false;
    }


}