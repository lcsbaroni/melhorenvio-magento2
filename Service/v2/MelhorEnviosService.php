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
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $host;

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
     * @param string $environment
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        string $token,
        string $environment,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->token = $token;
        $this->environment = $environment;
        $this->logger = $logger;

        $prefix = "";
        if ($this->environment == \Lb\MelhorEnvios\Model\System\Config\Source\Environment::SANDBOX) {
            $prefix = "sandbox.";
        }

        $this->host =  'https://'. $prefix .'melhorenvio.com.br';
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
        $parameters['host'] = $this->host;
        return $this->getConnector()->doRequest($function, $parameters);
    }
}
