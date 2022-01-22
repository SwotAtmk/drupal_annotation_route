<?php


namespace Drupal\annotation_route\Loaders;

use Drupal\Core\Routing\RouteCompiler;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Route;

/**
 * Class AnnotationRouteAnnotationClassLoader
 * @package Drupal\annotation_route\Loaders
 */
class AnnotationRouteAnnotationClassLoader extends AnnotationClassLoader
{

  /**
   * 配置路由
   * @param Route $route
   * @param \ReflectionClass $class
   * @param \ReflectionMethod $method
   * @param $annot
   */
  protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annot)
  {
    // 设置_controller
    $controller_default = $method->class."::".$method->name;
    $route->setDefault("_controller",$controller_default);
  }

  protected function createRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition)
  {
    $options["compiler_class"] = RouteCompiler::class;    // 设置由Drupal提供的路由编译器
    if (!isset($requirements["_permission"]) || empty($requirements["_permission"])) $requirements["_permission"] = "access content";
    return parent::createRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
  }
}
