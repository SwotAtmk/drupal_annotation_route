<?php


namespace Drupal\annotation_route;


/**
 * 注解接口类
 * Interface AnnotationInterface
 * @package Drupal\annotation_route
 */
interface AnnotationInterface
{
  /**
   * 获取注解class属性
   * @return mixed
   */
  public function getClass();

  /**
   * 设置class属性
   * @param $class
   * @return mixed
   */
  public function setClass($class);

}
