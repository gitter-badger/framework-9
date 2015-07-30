<?php
namespace Lebran\Event;

/**
 * With the Event\Manager can create hooks or plugins that will offer
 * monitoring of data, manipulation, conditional execution and much more.
 *
 * @package    Event
 * @version    2.0.0
 * @author     Roman Kritskiy <itoktor@gmail.com>
 * @license    GNU Licence
 * @copyright  2014 - 2015 Roman Kritskiy
 */
class Manager
{
    /**
     * Storage for listeners.
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * Storage for listeners responses.
     *
     * @var array
     */
    protected $responses = [];

    /**
     * Attaches the listener from the events manager.
     *
     * @param string   $name     The name of event.
     * @param callable $listener A function to be executed.
     * @param int      $priority The priority with which listeners are executed.
     *
     * @return object Manager object
     * @throws \Lebran\Event\Exception
     */
    public function attach($name, callable $listener, $priority = 100)
    {
        if (!is_int($priority)) {
            throw new Exception('Argument "priority" must be integer.');
        }

        if (empty($this->listeners[$name])) {
            $this->listeners[$name] = new \SplPriorityQueue();
        }

        $this->listeners[$name]->insert($listener, $priority);

        return $this;
    }

    /**
     * Detaches the listener from the events manager.
     *
     * @param string   $name     The name of event.
     * @param callable $listener A function to be executed.
     *
     * @return object Manager object.
     */
    public function detach($name, callable $listener)
    {
        if ($this->hasListeners($name)) {
            $queue     = $this->listeners[$name];
            $new_queue = new \SplPriorityQueue();
            $queue->setExtractFlags($queue::EXTR_BOTH);
            foreach ($queue as $callback) {
                if ($callback['data'] !== $listener) {
                    $new_queue->insert($callback['data'], $callback['priority']);
                }
            }
            $this->listeners[$name] = $new_queue;
        }

        return $this;
    }

    /**
     * Detaches the group or all listeners from the events manager.
     *
     * @param string $name The name of event.
     *
     * @return object Manager object.
     */
    public function detachAll($name = null)
    {
        if ($name) {
            if ($this->hasListeners($name)) {
                unset($this->listeners[$name]);
            }
        } else {
            $this->listeners = [];
        }

        return $this;
    }

    /**
     * Executes attached listeners for this event.
     *
     * @param string $name   The name of event.
     * @param object $object Which it was caused by an object event.
     * @param array  $data   Additional information.
     *
     * @return object Manager object.
     * @throws \Lebran\Event\Exception
     */
    public function fire($name, $object, array $data = null)
    {
        if (!is_object($object)) {
            throw new Exception('Argument "object" must be object where fire event.');
        }

        $names = [$name];
        while (($pos = strrpos($name, '.'))) {
            $name = $listeners[] = substr($name, 0, $pos);
        }
        $params = [$object];
        if ($data) {
            $params[] = $data;
        }

        foreach ($names as $name) {
            if ($this->hasListeners($name)) {
                foreach ($this->listeners[$name] as $listener) {
                    $response = call_user_func_array($listener, $params);
                    if (!is_null($response)) {
                        if ($response === false) {
                            break;
                        } else {
                            $this->responses[$name][] = $response;
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Check whether the listeners contains a event by a name.
     *
     * @param string $name The name of event.
     *
     * @return bool True, if exists, then - false.
     */
    public function hasListeners($name)
    {
        return isset($this->listeners[$name]);
    }

    /**
     * Gets group or all listeners.
     *
     * @param string $name The name of event.
     *
     * @return array An array of listeners if exists, then - null.
     */
    public function getListeners($name = null)
    {
        return $name?($this->hasListeners($name)?$this->listeners[$name]:null):$this->listeners;
    }

    /**
     * Gets listeners responses.
     *
     * @return array Listeners responses.
     */
    public function getResponses()
    {
        return $this->responses;
    }
}