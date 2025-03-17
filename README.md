Adicionar arquivos em na pasta src/Mailer/Transport

Adicionar em config/packages/mailer.yaml


```
	mailer:
		transport_factory: 'App\Mailer\Transport\WabTransportFactory'
````

Adicionar em .env.local

```
MAILER_DSN=wmailer+api://APIKEY@default
````
