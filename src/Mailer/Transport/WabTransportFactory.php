<?php

namespace WabNinjas\WmailerTransport\Mailer\Transport;

use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Twig\Environment;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;




final class WabTransportFactory extends AbstractTransportFactory
{
    private Environment $twig;

    public function __construct(Environment $twig, ?TraceableHttpClient $client = null, ?EventDispatcherInterface $dispatcher = null, ?LoggerInterface $logger = null)
    {
        parent::__construct( $dispatcher, $client, $logger);
        $this->twig = $twig;
    }

    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();

        if (!\in_array($scheme, $this->getSupportedSchemes(), true)) {
            throw new UnsupportedSchemeException($dsn, 'wmailer', $this->getSupportedSchemes());
        }

        $host = $dsn->getHost();
        $port = $dsn->getPort();
        $apiToken = $this->getUser($dsn);

        return (new WabApiTransport($apiToken, $host, $this->twig, $this->client, $this->dispatcher, $this->logger))->setPort($port);
    }

    protected function getSupportedSchemes(): array
    {
        return ['wmailer', 'wmailer+api'];
    }
}
