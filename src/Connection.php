<?php
/**
 * Created by PhpStorm.
 * User: Choate
 * Date: 2018/3/12
 * Time: 15:08
 */

namespace choate\smses;


class Connection
{
    /**
     * @var AdapterInterface[]
     */
    private $adapterCollections;

    /**
     * @var boolean
     */
    private $debug;

    /**
     * @var \Closure
     */
    private $logger;

    private $maxTries = 3;

    /**
     * Connection constructor.
     *
     * @param AdapterInterface[] $adapterCollections
     */
    public function __construct(Array $adapterCollections)
    {
        $this->adapterCollections = $adapterCollections;
    }

    /**
     * @return int
     */
    public function getMaxTries()
    {
        return $this->maxTries;
    }

    /**
     * @param int $maxTries
     */
    public function setMaxTries($maxTries)
    {
        $this->maxTries = $maxTries;
    }

    /**
     * @param int $mobile
     * @param string $content
     * @param string $region
     * @return boolean
     * @throws Exception
     */
    public function send($mobile, $content, $region = '86')
    {
        $collections = $this->getAdapterCollections();
        $maxTries = $this->maxTries;

        while ($maxTries > 0) {
            foreach ($collections as $category => $adapter) {
                try {
                    if ($adapter->send($mobile, $content, $region)) {
                        return true;
                    } else {
                        $this->writeLog($category, $adapter->getName(), "发送短信失败[+{$region}{$mobile}]: $content");
                    }
                } catch (Exception $e) {
                    $this->writeExceptionLog($category, "发送短信失败[+{$region}{$mobile}]: $content \n" . $adapter->getName(), $e);
                }
            }

            $maxTries--;
        }

        return false;
    }

    /**
     * @param array $mobiles
     * @param string $content
     * @param string $region
     * @return boolean
     * @throws Exception
     */
    public function batchSend(Array $mobiles, $content, $region = '86')
    {
        $collections = $this->getAdapterCollections();
        $maxTries = $this->maxTries;

        while ($maxTries > 0) {
            foreach ($collections as $category => $adapter) {
                try {
                    if ($adapter->batchSend($mobiles, $content, $region)) {
                        return true;
                    }
                } catch (Exception $e) {
                    $this->writeExceptionLog($category, $adapter->getName(), $e);
                }
            }
            $maxTries--;
        }

        return false;
    }

    /**
     * @return AdapterInterface[]
     */
    protected function getAdapterCollections()
    {
        $this->shuffleAssoc($this->adapterCollections);

        return $this->adapterCollections;
    }

    protected function shuffleAssoc(array &$array) {
        $result = [];
        $keys = array_keys($array);
        shuffle($keys);
        foreach($keys as $key) {
            $result[$key] = $array[$key];
        }

        $array = $result;

        return true;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param \Closure $logger
     */
    public function setLogger(\Closure $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $category
     * @param $appName
     * @param Exception $exception
     * @throws Exception
     */
    protected function writeExceptionLog($category, $appName, $exception)
    {
        if (empty($this->logger) || $this->isDebug() === false) {
            new InvalidConfigException('The "logger" or "debug" property must be set.');
        }

        $this->writeLog($category, $appName, $exception->getMessage());

        if ($this->isDebug()) {
            throw $exception;
        }
    }

    protected function writeLog($category, $appName, $message)
    {
        if ($this->logger) {
            call_user_func($this->logger, $category, $appName, $message);
        }
    }
}