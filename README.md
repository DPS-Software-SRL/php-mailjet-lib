# php-mailjet-lib
## Envio de mails via Mailjet

### PREREQUISITOS

**Variables $_ENV requeridas**
```
 $_ENV['MJ_APIKEY_PUBLIC']  // Tokens de API de Mailjet
 $_ENV['MJ_APIKEY_PRIVATE'] // Tokens de API de Mailjet
 $_ENV['ENTORNO']           // [ 'DEV' | 'TEST' | 'PROD' ] Si NO es PROD, aparecerá en el SUBJECT
 $_ENV['MJ_FROM_EMAIL']     // Email de salida
 $_ENV['MJ_FROM_NAME']      // Nombre de email de salida
 $_ENV['SEND_EMAILS']       // [ 'true' | 'false' ]
```

### EJEMPLOS

**Un solo destinatario** (String)
```
$to      = 'usuario1@correo.com';
```

**Varios destinatiarios** (Array)
```
$to      = ['usuario1@correo.com', 'usuario2@correo.com'];
```

**Uso simple**
```
$to      = ( string o array de direcciones );
$subject = 'Titulo del correo';
$text    = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod';
$status  = Dps\Mailer::send( $to, $subject, $text );
```

**Uso con adjuntos**
```
$pathToAttachment = $pathTo . '/info.jpg'
$text             = "Lorem ipsum <img src='cid:info.jpg'>dolor sit amet";
$status = Dps\Mailer::addAttachment( $pathToAttachment ) 
                    ::send( $to, $subject, $text );
```

**Uso con adjuntos embebidos en el cuerpo. Ver uso particular de IMG dentro del BODY**
```
$pathToAttachment = $pathTo . '/info.jpg'
$text             = "Lorem ipsum <img src='cid:info.jpg'>dolor sit amet";
$status = Dps\Mailer::addInlineFile( $pathToAttachment ) 
                    ::send( $to, $subject, $text );
```

**Uso con adjuntos embebido en el final del cuerpo del correo**
```
$status = Dps\Mailer::addLogoAlPie( $pathToInlineAttachment )
                    ::send( $to, $subject, $text );
```

**Uso mas completo**
```
$status = Dps\Mailer::addAttachment( $pathToAttachment1 ) 
                    ::addAttachment( $pathToAttachment2 ) 
                    ::addLogoAlPie( $pathToInlineAttachment )
                    ::send( $to, $subject, $text );
```

**Respuesta del envio**
```
  $status = [
      'success' => true,    // boolean,
      'data'    => [ ... ], // Info de cada mensaje enviado con ID para seguimiento en MailJet
      'error'   => 'OK',    // string con el error, o 'OK' en caso de exito
  ];
```