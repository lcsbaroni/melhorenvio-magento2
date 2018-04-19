<?php
namespace Lb\MelhorEnvios\Service\v2;

use Lb\MelhorEnvios\Service\v2\Environment\Connector;

class MelhorEnviosService
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Lb\MelhorEnvios\Service\v2\Environment\Connector
     */
    protected $connector;

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
     * @return Connector
     */
    public function getConnector() : Connector
    {
        if (!$this->connector instanceof Connector) {
            $this->connector = new Connector($this->token, $this->logger);
        }

        return $this->connector;
    }

    /**
     * @param $function
     * @param $parameters
     * @return mixed
     */
    public function doRequest($function, $parameters)
    {
        $parameters['host'] = 'https://melhorenvio.com.br';
        return $this->getConnector()->doRequest($function, $parameters);
    }
}
