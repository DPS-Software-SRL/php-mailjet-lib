# php-mailjet-lib
## Envio de mails via Mailjet

### PREREQUISITOS

**Variables $_ENV requeridas**
```
 $_ENV['MJ_APIKEY_PUBLIC']  // Tokens de API de Mailjet
 $_ENV['MJ_APIKEY_PRIVATE'] // Tokens de API de Mailjet
 $_ENV['ENVIRONMENT']       // [ 'DEV' | 'TEST' | 'PROD' ] Si NO es PROD, aparecerá en el SUBJECT
 $_ENV['MJ_FROM_EMAIL']     // Email de salida
 $_ENV['MJ_FROM_NAME']      // Nombre de email de salida
 $_ENV['SEND_EMAILS']       // [ 'true' | 'false' ]
```
**Variables $_ENV opcionales**
```
// SEND_EMAILS_TO (String) 
// Si esta variable esta seteada a una o varias direcciones separadas por COMA, 
// Agregara dichas direcciones a todos los envios. 
// Util para controlar los envios y/o tener copias de todo lo que envia un sistema
SEND_EMAILS_TO = 'yo@softwaredps.com.ar'

// SEND_EMAILS_TO_ONLY_FOR_DEBUG (String) 
// Similar a la anterior
// Enviará los correos SOLO A ESTAS DIRECCIONES
// omitiendo las indicadas desde el codigo
// Util para poder validar durante el desarrollo los envios sin alterar el codigo.
SEND_EMAILS_TO_ONLY_FOR_DEBUG = 'yo@softwaredps.com.ar'
```

### EJEMPLOS

**Un solo destinatario** (String)
```
$to      = 'usuario1@correo.com';
```

**Varios destinatiarios** (String)
```
$to      = 'usuario1@correo.com, usuario2@correo.com';
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