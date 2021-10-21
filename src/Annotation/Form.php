<?php


namespace Drupal\annotation_route\Annotation;

use Drupal\annotation_route\AnnotationInterface;

/**
 * Class FormRouteAnnotation
 * @package Drupal\annotation_route\Annotation
 * @Annotation
 */
class Form implements AnnotationInterface
{
  private $path;
  private $name;
  private $title;
  private $requirements = [];
  private $options = [];
  private $defaults = [];
  private $host;
  private $methods = [];
  private $schemes = [];
  private $condition;
  private $class=null;

  /**
   * @param array $data 数组 Key/Value 参数
   *
   * @throws \BadMethodCallException
   */
  public function __construct(array $data)
  {
    if (isset($data['value'])) {
      $data['path'] = $data['value'];
      unset($data['value']);
    }

    foreach ($data as $key => $value) {
      $method = 'set'.str_replace('_', '', $key);
      if (!method_exists($this, $method)) {
        throw new \BadMethodCallException(sprintf('未知属性 "%s" 在注释上 "%s".', $key, static::class));
      }
      $this->$method($value);
    }
  }

  public function setPath($path)
  {
    $this->path = $path;
  }

  public function getPath()
  {
    return $this->path;
  }

  public function setHost($pattern)
  {
    $this->host = $pattern;
  }

  public function getHost()
  {
    return $this->host;
  }

  public function setName($name)
  {
    $this->name = $name;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getTitle()
  {
    return $this->title;
  }

  public function setTitle($title)
  {
    $this->title = $title;
  }

  public function setRequirements($requirements)
  {
    $this->requirements = $requirements;
  }

  public function getRequirements()
  {
    return $this->requirements;
  }

  public function setOptions($options)
  {
    $this->options = $options;
  }

  public function getOptions()
  {
    return $this->options;
  }

  public function setDefaults($defaults)
  {
    $this->defaults = $defaults;
  }

  public function getDefaults()
  {
    return $this->defaults;
  }

  public function setSchemes($schemes)
  {
    $this->schemes = \is_array($schemes) ? $schemes : [$schemes];
  }

  public function getSchemes()
  {
    return $this->schemes;
  }

  public function setMethods($methods)
  {
    $this->methods = \is_array($methods) ? $methods : [$methods];
  }

  public function getMethods()
  {
    return $this->methods;
  }

  public function setCondition($condition)
  {
    $this->condition = $condition;
  }

  public function getCondition()
  {
    return $this->condition;
  }

  public function getClass()
  {
    return $this->class;
  }

  public function setClass($class)
  {
    $this->class = $class;
    return $this;
  }
}
