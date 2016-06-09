<?php

namespace pushprom\yii2;

class ConnectionProxy extends \yii\base\Component
{
    public $url = "udp://127.0.0.1:9090";
    public $connection = null;
    public $constLabels = [];

    public function init()
    {
        parent::init();
        $this->connection = new \pushprom\Connection(
            $this->url, $this->constLabels, function ($message) {
            \Yii::warning($message);
        }
        );
    }

    /**
     * Proxy calls to the real connection
     *
     * @param string $methodName
     * @param array  $arguments
     */
    public function __call($methodName, $arguments)
    {
        if ($this->connection && method_exists($this->connection, $methodName)) {
            call_user_func_array([$this->connection, $methodName], $arguments);
        }
    }

    // some helper methods

    /**
     * Logs, using a Counter metric, http responses. A label is set for every response code.
     */
    public function logHttpResponse($statusCode)
    {
        $counter = new \pushprom\Counter(
            $this->connection,
            'http_response',
            "count http responses",
            ['status' => $statusCode]
        );
        $counter->inc();
    }

    /**
     * Logs, using a Summary metric, the response time in milliseconds measured by \Yii::getLogger()->getElapsedTime()
     */
    public function logResponseTimeMs()
    {
        $summary = new \pushprom\Histogram(
            $this->connection,
            'response_time_ms',
            "summary of the response time of http requests"
        );
        return $summary->observe(floatval(bcmul(\Yii::getLogger()->getElapsedTime(), 1000)));
    }


    private function stat($methodName, $type, $name, $help, $labels = [], $value = null)
    {
        $fullType = "pushprom\\$type";
        // create an instance of the metric
        $klass  = new \ReflectionClass($fullType);
        $metric = $klass->newInstanceArgs([$this->connection, $name, $help, $labels]);

        if (method_exists($metric, $methodName)) {
            $r    = new \ReflectionMethod ($fullType, $methodName);
            $args = [];
            if ($r->getNumberOfRequiredParameters() == 1) {
                array_push($args, $value);
            }
            // call the method
            call_user_func_array([$metric, $methodName], $args);
        } else {
            throw new \Exception("Method '$methodName' does not exist in '$fullType'");
        }
    }

    /**
     * Sets the value of a Counter or a Gauge metric
     *
     * @param float $value
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function set($value, $type, $name, $help, $labels = [])
    {
        $this->stat('set', $type, $name, $help, $labels, $value);
    }

    /**
     * Increments a Counter or a Gauge metric
     *
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function inc($type, $name, $help, $labels = [])
    {
        $this->stat('inc', $type, $name, $help, $labels);
    }

    /**
     * Decrements a value of a Gauge metric
     *
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function dec($type, $name, $help, $labels = [])
    {
        $this->stat('dec', $type, $name, $help, $labels);
    }

    /**
     * Add a value to a Counter or a Gauge metric
     *
     * @param float $value
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function add($value, $type, $name, $help, $labels = [])
    {
        $this->stat('add', $type, $name, $help, $labels, $value);
    }

    /**
     * Subtract a value from a Gauge metric
     *
     * @param float $value
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function sub($value, $type, $name, $help, $labels = [])
    {
        $this->stat('sub', $type, $name, $help, $labels, $value);
    }

    /**
     * Observe a value. Valid for Histogram and Summary metrics.
     *
     * @param float $value
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function observe($value, $type, $name, $help, $labels = [])
    {
        $this->stat('observe', $type, $name, $help, $labels, $value);
    }


}

