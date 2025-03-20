Adicionar arquivos na pasta src/Mailer/Transport

Adicionar em config/services.yaml


```
services:	
	App\Mailer\Transport\WabTransportFactory:
        tags: ['mailer.transport_factory']
````

Adicionar em .env.local

```
MAILER_DSN=wmailer+api://APIKEY@default
````
