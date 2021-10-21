<?php


namespace Drupal\annotation_route\Loaders;

use Symfony\Component\Config\Resource\ResourceInterface;

class AnnotationItem implements \IteratorAggregate, \Countable
{
  /**
   * @var object[]
   */
  private $instanceList = [];

  /**
   * @var array
   */
  private $resources = [];

  public function __clone()
  {
    foreach ($this->instanceList as $name => $item) {
      $this->instanceList[$name] = clone $item;
    }
  }

  /**
   * @return \ArrayIterator
   */
  public function getIterator()
  {
    return new \ArrayIterator($this->instanceList);
  }

  /**
   * @return int
   */
  public function count()
  {
    return \count($this->instanceList);
  }

  /**
   * @param $name
   * @param $instanceList
   */
  public function add($name, $instanceList)
  {
    unset($this->instanceList[$name]);

    $this->instanceList[$name] = $instanceList;
  }

  /**
   * @return object[]
   */
  public function all()
  {
    return $this->instanceList;
  }

  /**
   * @param $name
   * @return object|null
   */
  public function get($name)
  {
    return $this->instanceList[$name] ?? null;
  }

  /**
   * @param $name
   */
  public function remove($name)
  {
    foreach ((array) $name as $n) {
      unset($this->instanceList[$n]);
    }
  }

  /**
   * @param AnnotationItem $collection
   */
  public function addCollection(self $collection)
  {
    foreach ($collection->all() as $name => $instance) {
      unset($this->instanceList[$name]);
      $this->instanceList[$name] = $instance;
    }

    foreach ($collection->getResources() as $resource) {
      $this->addResource($resource);
    }
  }

  /**
   * Returns an array of resources loaded to build this collection.
   *
   * @return ResourceInterface[] An array of resources
   */
  public function getResources()
  {
    return array_values($this->resources);
  }

  /**
   * Adds a resource for this collection. If the resource already exists
   * it is not added.
   */
  public function addResource(ResourceInterface $resource)
  {
    $key = (string) $resource;

    if (!isset($this->resources[$key])) {
      $this->resources[$key] = $resource;
    }
  }
}
