# Escenario D · Logística de los Reyes Magos

API REST en PHP para gestionar caravanas, paradas e itinerarios. La salida de una caravana depende de la consulta SOAP a la Estrella de Belén.

## Ejecutar en Windows con XAMPP

Se requiere PHP 8.2 con cURL y SimpleXML. Abre dos ventanas de CMD en la carpeta del proyecto.

En la primera ventana, inicia el simulador:

```cmd
"C:\xampp\php\php.exe" -S 127.0.0.1:8001 mock\estrella-prueba.php
```

En la segunda ventana, configura las variables y ejecuta la API:

```cmd
set "REYES_TOKEN_JEFE=jefe-local-prueba"
set "REYES_TOKEN_PAJE=paje-local-prueba"
set "REYES_SOAP_URL=http://127.0.0.1:8001/"
"C:\xampp\php\php.exe" -S 127.0.0.1:8000 index.php
```

El archivo `.env.example` enumera las variables. PHP recibe los valores del entorno de CMD. Las claves mostradas son ejemplos para pruebas locales. En Postman, selecciona Bearer Token y usa la clave de jefe para operaciones protegidas. La clave de paje permite probar el 403. Usa No Auth para el 401.

Por defecto los datos se guardan en `../reyes-magos-caravanas.json`, fuera del directorio del proyecto. Esto permite recuperar los registros de la versión anterior si ambas carpetas del proyecto están en el mismo directorio padre. Usa `REYES_DATOS` para elegir una ruta absoluta alternativa. Para empezar las pruebas con datos vacíos sin borrar los anteriores, elige un archivo nuevo mediante esa variable.

## Arquitectura

Postman envía HTTP a `index.php`, que selecciona la ruta y verifica el permiso con `src/Http.php`. Los controladores reciben los datos y llaman a los servicios. Los servicios de caravanas y paradas aplican las reglas. `src/Repositorio.php` lee y actualiza el JSON bajo un bloqueo. El adaptador `src/EstrellaAdapter.php` traduce la consulta y la respuesta SOAP. Ante un fallo o espera superior a tres segundos, el servicio conserva el estado y el manejador de errores devuelve 503.

## SOAP de prueba

El simulador local responde NUBLADA para Persia, devuelve un Fault para Herodes y responde VISIBLE para Babilonia. El endpoint del docente puede configurarse en `REYES_SOAP_URL`. La integración con su servicio depende de verificar la dirección y el contrato SOAP que comparta.

## Microservicios

Separaría la gestión del viaje de la navegación. El servicio de viaje tendría caravanas, ofrendas, estados y paradas. Las paradas permanecerían junto con las caravanas porque la regla de llegada necesita comprobar su estado en la misma operación. El servicio de navegación tendría las observaciones de visibilidad y las estimaciones por ruta. Si navegación falla el 24 a las 23:00, viaje seguiría atendiendo consultas y operaciones que no requieren visibilidad. Una solicitud de salida respondería 503 tras el fallo o el tiempo de espera, y mantendría la caravana detenida. Así se podría reintentar cuando el servicio se recupere.
