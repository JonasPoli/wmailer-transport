**versão 1.0**

Adicionar arquivos na pasta src/Mailer/Transport

````
git clone https://github.com/JonasPoli/wmailer-transport.git src/Mailer/Transport
rm -rf ./src/Mailer/Transport/.git
````

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
