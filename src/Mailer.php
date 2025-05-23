<?php

namespace Dps;

use Exception;
use \Mailjet\Resources;
use \Mailjet\Client;

/**
 * Envia correos electronicos usando MAILJET.com
 * 
 * @requires $_ENV['ENVIRONMENT'] // [ 'DEV' | 'TEST' | 'PROD' ]
 * @requires $_ENV['MJ_FROM_EMAIL']
 * @requires $_ENV['MJ_FROM_NAME']
 * @requires $_ENV['MJ_APIKEY_PUBLIC']
 * @requires $_ENV['MJ_APIKEY_PRIVATE']
 * @requires $_ENV['SEND_EMAILS'] // [ 'true' | 'false' ]
 * 
 * @use $_ENV['SEND_EMAILS_TO'] string Direcciones de correo que se agregan siempre a un envio. Para usar como debug o control
 * @use $_ENV['SEND_EMAILS_TO_ONLY_FOR_DEBUG'] string Direcciones de correo a las que se enviara el mail solamente, sin importar lo indicado desde PHP. Para debug solamente.
 */
final class Mailer
{
    private static $attachments = [];
    private static $body;
    private static $inlineFiles = [];
    private static $destinatarios = [];
    private static $mj;
    private static $fromEmail;
    private static $fromName;
    private static $subject;
    private static $withLogoAlPie = '';

    /**
     * Configura Mailjet
     * @throws Exception En caso de que las variables de entorno no esten cargadas
     * @return void
     */
    static private function config()
    {
        self::validate();

        $apikey          = $_ENV['MJ_APIKEY_PUBLIC'];
        $apisecret       = $_ENV['MJ_APIKEY_PRIVATE'];
        self::$fromName  = $_ENV['MJ_FROM_NAME'];
        self::$fromEmail = $_ENV['MJ_FROM_EMAIL'];
        
        self::$mj = new Client($apikey, $apisecret, true, ['version' => 'v3'] );        
    }


    /**
     * verifica si ciertas variables de entorno están definidas 
     * y lanza una excepción si alguna de ellas falta.
     * 
     * @throws \Exception
     * @return void
     */
    static private function validate() {

        $envs = ['MJ_APIKEY_PUBLIC', 'MJ_APIKEY_PRIVATE', 'MJ_FROM_NAME', 'MJ_FROM_EMAIL'];
        foreach ( $envs as $var) {
          if( ! isset( $_ENV[ $var ] ) ) {
            throw new Exception("Env var $var is required");
          }
        }
        
    }

    /**
     * Devuelve un array para enviar una respuesta de error
     * 
     * @param string $msj
     * @return array
     */
    static private function respuestaFalsa( string $msj ) {
        return [
            'success' => 0,
            'data'    => [],
            'error'   => $msj
        ];
    }

    /**
     * Lee el archivo indicado para usarlo en las funciones de adjuntos
     * 
     * @param string $path Path al adjunto
     * @param string $name Nombre del archivo como se verá en el correo (opcional)
     * @return array
     */
    static private function readFile( string $path, string $name = '' ) {
        $file   = file_get_contents( $path );
        $base64 = base64_encode( $file );

        $finfo  = finfo_open(FILEINFO_MIME_TYPE);
        $mime   = finfo_file($finfo, $path);
        finfo_close($finfo);

        if( $name == '' ) {
            $name = basename( $path );
        }

        return [ 
            'Content'      => $base64,
            'Content-type' => $mime,
            'Filename'     => $name 
        ];
    }


    /**
     * Adds an attachment to the list of attachments files.
     *
     * @param string $path The path to the file.
     * @return self The current class instance.
     */
    static public function addAttachment( string $path, string $name = '' ) {
        self::$attachments[] = self::readFile( $path, $name );
        return new self();
    }


    /**
     * Agrega una imagen (para uso inline)
     * Desde el body del mensaje se debe usar asi:
     * texto ... <img src='cid:archivo.png'> ... texto
     *
     * @param string $path The path to the file.
     * @return self The current class instance.
     */
    static public function addInlineFile( string $path ) {
        self::$inlineFiles[] = self::readFile( $path );
        return new self();
    }
    
    
    static public function addLogoAlPie( $path ) {
        self::addInlineFile( $path );
        self::$withLogoAlPie = basename( $path );        
        return new self();
    }


    /**
     * Envia un correo electronico
     * @param string $to      String|Array con Direccion(es) de correo donde enviar el email
     * @param string $subject Titulo del email
     * @param string $body    Texto HTML del mensaje     
     * @return array Array [ 'success' => 1,
     *                       'data' => [
     *                              'Sent' => [
     *                                      0 => [
     *                                              'Email' => 'mscarnatto@gmail.com'
     *                                              'MessageID' => 1152921526991920509
     *                                              'MessageUUID' => 'c3be1587-675c-4965-a479-5c064650429a'
     *                                          ]
     *                                  ]
     *                          ]
     *                       'error' => 'OK' 
     *                       ]
     */
     static public function send( $to, string $subject, string $body ) : array
     {
        self::$subject = $subject;
        self::$body    = $body;

        // Envio de mails habilitado desde .env ?
        if( isset( $_ENV['SEND_EMAILS'] ) && trim( strtolower( $_ENV['SEND_EMAILS'] ) ) == 'false' ) {
            return self::respuestaFalsa( 'Envío de emails desactivado' );
        }

        try {
            self::setDestinatarios( $to );
        
        } catch (Exception $e) {
            return self::respuestaFalsa( $e->getMessage() );
        }       

        // Agrega como destinatarios a los indicados en .ENV
        if( isset( $_ENV['SEND_EMAILS_TO'] ) && trim( $_ENV['SEND_EMAILS_TO'] ) != '' ) {
            self::setDestinatarios( $_ENV['SEND_EMAILS_TO'] );
        }

        // Solo para debug
        // Solo enviarar  a los indicados en .ENV sin importar lo indicado por codigo
        if( isset( $_ENV['SEND_EMAILS_TO_ONLY_FOR_DEBUG'] )  && trim( $_ENV['SEND_EMAILS_TO_ONLY_FOR_DEBUG'] ) != '' ) {
            self::$destinatarios = [];
            self::setDestinatarios( $_ENV['SEND_EMAILS_TO_ONLY_FOR_DEBUG'] );
        }

        self::config();

        self::setSubject();
        
        self::setBody();    

        $toSend = [
            'FromEmail'          => self::$fromEmail,
            'FromName'           => self::$fromName,
            'Recipients'         => self::$destinatarios,
            'Subject'            => self::$subject,
            'Attachments'        => self::$attachments,
            'Inline_attachments' => self::$inlineFiles,
            'Html-part'          => self::$body
        ];

        $response = self::$mj->post(Resources::$Email, ['body' => $toSend]);

        self::clearAttachments();
        self::clearDestinatarios();

        $salida = [
            'success' => $response->success(),
            'data'    => $response->getData(),
            'error'   => $response->getReasonPhrase()
        ];
         
        return $salida;        
    }


    /**
     * Si corresponde, le concatena al final del body, el logo agregado desde addLogoAlPie()
     */
    static private function setBody() {
            
        // agrego logo al pie del texto si corresponde
        if( self::$withLogoAlPie != '' ) {
            if( stripos( self::$body, '</body>' ) !== false ) {
                self::$body = str_ireplace( '</body>', "<p>&nbsp;<p><img style='max-width:400px' src='cid:".self::$withLogoAlPie."'></body>", self::$body );
                
            } else {
                self::$body .= "<p>&nbsp;<p><img style='max-width:400px' src='cid:".self::$withLogoAlPie."'>";
            }
        }
    }
    


    /**
     *  Preparo el array de destinatarios con el formato necesario de mailjet
     *  @param mixed $to ( string | array ) String con uno o varias direcciones separadas por coman (,) o un array de direcciones
     **/
    static private function setDestinatarios( $to ) {        
        // $to puede ser string de correos separados por "coma' o un array. 
        // Si es string lo transformo a array primero
        if( is_string( $to ) ) {
            $to = explode(',', $to);
        } 
        
        if( is_array( $to ) ) {
            // self::$destinatarios = [];
            foreach ($to as $direccion) {
                if (filter_var($direccion, FILTER_VALIDATE_EMAIL)) {
                    self::$destinatarios[] = ['Email' => $direccion];
                }
            }

            if( count( self::$destinatarios ) == 0 ) {
                throw new Exception("Destinatarios vacios/incorrectos");
            }

        } else {
            throw new Exception("Destinatarios debe ser string o array");
        }          
    }


    /**
     * Agrego el entorno al SUBJECT para diferenciarlo de PROD
     * 
     */
    static private function setSubject( ) {        
        if( isset($_ENV['ENVIRONMENT']) && trim( strtolower( $_ENV['ENVIRONMENT'] ) ) != "prod" ) {
            self::$subject = "[{$_ENV['ENVIRONMENT']}] " . self::$subject;
        }       
    }

             
    /**
     * Retrieve specific information on the type of content, tracking, sending and delivery for a specific processed message.
     * @link https://dev.mailjet.com/email/reference/messages#v3_get_message_message_ID
     * @param int $id
     * @return array
     */
    static public function Message( int $id ) : array
    {    
        self::config();
        $response = self::$mj->get( Resources::$Message, [ 'id' => $id ] );
        return $response->getData();
    }

    
    static public function clearDestinatarios( ) {
        self::$destinatarios = [];
    }


    static public function clearAttachments() {
        self::$attachments = [];        
    }

    
    /**
     * Retrieve sending / size / spam information about a specific message ID.
     * @link https://dev.mailjet.com/email/reference/messages#v3_get_messageinformation_message_ID
     * @param int $id
     * @return array
     */
    static public function Messageinformation( int $id ) : array
    {    
        self::config();
        $response = self::$mj->get( Resources::$Messageinformation, [ 'id' => $id ] );
        return $response->getData();
    }
}
