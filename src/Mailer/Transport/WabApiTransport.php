<?php

namespace WabNinjas\WmailerTransport\Mailer\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Twig\Environment;

final class WabApiTransport extends AbstractApiTransport
{
    private Environment $twig;

    public function __construct(
        #[\SensitiveParameter] private string $apiToken,
        private string $hostName,
        Environment $twig, // <-- Twig adicionado aqui
        ?HttpClientInterface $client = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($client, $dispatcher, $logger);
        $this->setHost($hostName);
        $this->twig = $twig;
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        if ($email instanceof TemplatedEmail) {
            (new BodyRenderer($this->twig))->render($email);
        }

        $response = $this->client->request('POST', 'https://' . $this->getEndpoint() . '/v1/send', [
            'json' => $this->getPayload($email, $envelope),
        ]);

        try {
            $statusCode = $response->getStatusCode();
            $result = $response->toArray(false);
        } catch (DecodingExceptionInterface $e) {
            throw new HttpTransportException('Unable to send an email: ' . $response->getContent(false) . sprintf(' (code %d).', $statusCode), $response);
        } catch (TransportExceptionInterface $e) {
            throw new HttpTransportException('Could not reach the remote server.', $response, 0, $e);
        }

        if (200 !== $statusCode) {
            throw new HttpTransportException('Unable to send an email: ' . $result['message'] . sprintf(' (code %d).', $statusCode), $response);
        }

        return $response;
    }

    public function __toString(): string
    {
        return sprintf('wmailer+api://%s', $this->getEndpoint());
    }

    private function getPayload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'key' => $this->apiToken,
            'mailFrom' => $envelope->getSender()->getAddress(),
            'nameFrom' => $envelope->getSender()->getName(),
            'mailTo' => array_map(fn ($address) => $address->getAddress(), $this->getRecipients($email, $envelope)),
            'subject' => $email->getSubject(),
        ];
        if ($email->getHtmlBody()) {
            $payload['body'] = $email->getHtmlBody();
        } elseif ($email->getTextBody()) {
            $payload['body'] = $email->getTextBody();
        }
        if ($attachments = $this->prepareAttachments($email)) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    private function prepareAttachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'name' => $attachment->getFilename(),
                'content_type' => $attachment->getContentType(),
                'data' => base64_encode($attachment->getBody()),
            ];
        }

        return $attachments;
    }

    private function getEndpoint(): string
    {
        return 'wmailer.wab.com.br';
    }
}
