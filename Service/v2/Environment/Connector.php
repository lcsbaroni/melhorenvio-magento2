<?php

namespace Lb\MelhorEnvios\Service\v2\Environment;

class Connector
{
    private $client;
    private $token;
    protected $logger;

    /**
     * MelhorEnviosService constructor.
     * @param string $token
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        string $token,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->token = $token;
        $this->logger = $logger;
    }

    /**
     * @return \Zend\Http\Client
     */
    public function getClient()
    {
        if (!$this->client instanceof \Zend\Http\Client) {
            $this->client = new \Zend\Http\Client();
            $options = [
                'maxredirects' => 0,
                'timeout' => 30
            ];
            $this->client->setOptions($options);
            $this->client->setHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
        }

        return $this->client;
    }

    /**
     * @param \Zend\Http\Client $client
     * @return \Zend\Http\Client
     */
    public function setClient(\Zend\Http\Client $client)
    {
        return $this->client = $client;
    }

    /**
     * @param $function
     * @param $parameters
     * @return mixed
     */
    public function doRequest($function, $parameters)
    {
        try {
            $method = isset($parameters['method']) ? $parameters['method'] : \Zend\Http\Request::METHOD_POST;

            $data = json_encode($parameters['data']);
            $this->getClient()->setMethod($method);
            $this->getClient()->setRawBody($data);
            $this->getClient()->setUri($parameters['host'] . $function);

            $this->logger->notice($data);

            $response = $this->getClient()->send();

        } catch( \Zend\Http\Client\Adapter\Exception\TimeoutException $e) {
            $this->logger->error($e);
            throw new \Exception($e->getMessage());
        }

        $this->logger->notice(json_encode($response->getBody()));
        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $responseErrorMessage = json_decode($response->getBody());

            if (empty($responseErrorMessage) || !property_exists($responseErrorMessage, 'message')) {
                $responseErrorMessage = new \stdClass();
                $responseErrorMessage->message = "Melhor envios - Transaction Failed!";
            }

            $errorsMessages = "";
            if (property_exists($responseErrorMessage, 'errors')) {
                foreach ($responseErrorMessage->errors as $error)
                $errorsMessages .= implode("\n", $error);
            }

            throw new \Exception($responseErrorMessage->message . $errorsMessages . sprintf(' (statusCode: %s)', (int) $response->getStatusCode()));
        }

        return $response->getBody();
    }
}
