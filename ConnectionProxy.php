<?php

namespace pushprom\yii2;

use pushprom\Connection;
use Yii;
use yii\base\Component;

/**
 * Class ConnectionProxy
 *
 * @package pushprom\yii2
 */
class ConnectionProxy extends Component
{
    /**
     * @var string
     */
    public $url = 'udp://127.0.0.1:9090';

    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var array
     */
    public $constLabels = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->connection = new \pushprom\Connection(
            $this->url,
            $this->constLabels,
            function ($message) {
                Yii::warning($message, __METHOD__);
            }
        );
    }

    /**
     * Proxy calls to the real connection
     *
     * @param string $methodName
     * @param array  $arguments
     *
     * @return mixed|void
     */
    public function __call($methodName, $arguments)
    {
        if ($this->connection && method_exists($this->connection, $methodName)) {
            call_user_func_array([$this->connection, $methodName], $arguments);
        }
    }

    /**
     * Logs a HTTP response using a Counter metric. A label is set for every response code.
     *
     * @param int $statusCode
     */
    public function logHttpResponse($statusCode)
    {
        $counter = new \pushprom\Counter(
            $this->connection,
            'http_response',
            'count http responses',
            ['status' => $statusCode]
        );
        $counter->inc();
    }

    /**
     * Logs the response time in milliseconds using a Summary metric measured by \Yii::getLogger()->getElapsedTime()
     */
    public function logResponseTimeMs()
    {
        $summary = new \pushprom\Histogram(
            $this->connection,
            'response_time_ms',
            "summary of the response time of http requests"
        );
        return $summary->observe(floatval(bcmul(Yii::getLogger()->getElapsedTime(), 1000)));
    }

    /**
     * @param string     $methodName
     * @param string     $type
     * @param string     $name
     * @param string     $help
     * @param array      $labels
     * @param string|int $value
     *
     * @throws \Exception
     */
    protected function stat($methodName, $type, $name, $help, array $labels = [], $value = null)
    {
        $fullType = "pushprom\\$type";
        // Create an instance of the metric.
        $class = new \ReflectionClass($fullType);
        $metric = $class->newInstanceArgs([$this->connection, $name, $help, $labels]);

        if (method_exists($metric, $methodName)) {
            $r = new \ReflectionMethod($fullType, $methodName);
            $args = [];
            if ($r->getNumberOfRequiredParameters() == 1) {
                array_push($args, $value);
            }
            // Call the method.
            call_user_func_array([$metric, $methodName], $args);
        } else {
            throw new \Exception("Method '$methodName' does not exist in '$fullType'");
        }
    }

    /**
     * Sets the value of a Counter or a Gauge metric.
     *
     * @param float  $value
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function set($value, $type, $name, $help, array $labels = [])
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
    public function dec($type, $name, $help, array $labels = [])
    {
        $this->stat('dec', $type, $name, $help, $labels);
    }

    /**
     * Add a value to a Counter or a Gauge metric
     *
     * @param float  $value
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function add($value, $type, $name, $help, array $labels = [])
    {
        $this->stat('add', $type, $name, $help, $labels, $value);
    }

    /**
     * Subtract a value from a Gauge metric
     *
     * @param float  $value
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function sub($value, $type, $name, $help, array $labels = [])
    {
        $this->stat('sub', $type, $name, $help, $labels, $value);
    }

    /**
     * Observe a value. Valid for Histogram and Summary metrics.
     *
     * @param float  $value
     * @param string $type
     * @param string $name
     * @param string $help
     * @param array  $labels
     */
    public function observe($value, $type, $name, $help, array $labels = [])
    {
        $this->stat('observe', $type, $name, $help, $labels, $value);
    }
}
