<?php
namespace Drupal\annotation_route;

use Doctrine\ORM\EntityManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AnnotationRouteClassFactory
 * @package Drupal\annotation_route
 */
class AnnotationRouteClassFactory
{

  /**
   * @var ContainerInterface $container 容器
   */
  private static $container;

  /**
   * 获取容器实例
   * @return \Symfony\Component\DependencyInjection\ContainerInterface|null
   */
  public static function getContainer(){
    if (self::$container){
      return self::$container;
    }
    return self::$container = \Drupal::getContainer();
  }

  /**
   * 获取服务
   * @param $id
   * @return object|null
   */
  public static function getService($id){
    return self::getContainer()->get($id);
  }

  /**
   * 判断是否debug环境
   * @return mixed
   */
  public static function isDebugEnv(){
    $twigConfig = self::getContainer()->getParameter("twig.config");
    return $twigConfig["debug"];
  }

  /**
   * 日志服务
   * @return \Drupal\Core\Logger\LoggerChannel|\Drupal\Core\Logger\LoggerChannelInterface
   */
  public static function logger(){
    return self::getContainer()->get("logger.factory")->get("annotation_route");
  }

  /**
   * 获取当前登录用户
   * @return \Drupal\Core\Session\AccountProxy|object|null
   */
  public static function User(){
    return self::getContainer()->get("current_user");
  }

  /**
   * @return Renderer|null
   */
  public static function renderer(){
    return self::getContainer()->get("renderer");
  }

  /**
   * 渲染内容
   * @param $content
   * @return mixed
   * @throws \Exception
   */
  public static function renderContent($content)
  {
    return self::renderer()->render($content);
  }

  /**
   * @param $entity_type_id
   * @return \Drupal\Core\Entity\EntityStorageInterface|mixed|object
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getEntityTypeStorage($entity_type_id){
    return self::getContainer()->get("entity_type.manager")->getStorage($entity_type_id);
  }

  /**
   * @param string $entity_type_id 实体类型ID，如：node、……
   * @param array $condition 查询条件
   * 查询条件使用方式：
   *    1、第一种使用形式：["field_name" => value,……]  注：查询以字段名称满足默认operator条件的value值
   *    2、第二种形式：["field_name"=>["value"=>条件值,"operator"=>"IN"],……] 注：在第一种形式的基础上新增了条件操作符operator，
   *    3、第三种形式：[
   *                   [
   *                     "conditionGroup"=>"OR",
   *                     "value" => [ "field_name" => ["value"=>条件值,"operator"=>"IN"],……]  # 这里的value类似于形式二，参考形式二实现
   *                   ],……
   *                ]
   *        注：conditionGroup参数用于启用条件组，可选用值："OR"、"AND"，分别采用OR条件组和AND条件组，
   *        可以组合类似SQL条件：SELECT * FORM {node_field_data} WHERE title=`我爱学Drupal` AND (nid IN (1,2,3……) OR created >= 1632844800)
   * @param string $operator 默认操作，包括："IN"、"LINK"、"="、">="等…… 数据库操作符
   * @param bool $isRenterNid 是否仅返回主键ID
   * @param string $defaultConjunction 默认查询连接符：AND、OR，用做查询条件的连接符
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function StorageLoadByProperties($entity_type_id,$condition,$operator="IN",$isRenterNid=false,$defaultConjunction="AND"){
    $Storage = self::getEntityTypeStorage($entity_type_id);
    $entity_query = $Storage->getQuery($defaultConjunction);
    $entity_query->accessCheck(FALSE);
    $tmp_operator = $operator;
    foreach ($condition as $name => $value) {
      if ($value == null||(is_string($value) and strtolower($value) == "is null")){
        $entity_query->condition($name, NULL, 'IS NULL');
      }elseif (is_string($value) and strtolower($value) == "is not null"){
        $entity_query->condition($name,NULL,"IS NOT NULL");
      }else{
        if (is_array($value)){
          if (isset($value["conditionGroup"]) && in_array($op=strtoupper($value["conditionGroup"]),["OR","AND"]) && is_array($value["value"])){
            $func_name = $op == "OR" ? "orConditionGroup": ($op == "AND" ? "andConditionGroup" : null);
            if ($func_name == null) throw new \Exception("请配置合法的conditionGroup参数，当前传入的conditionGroup为'${op}'，有效值：OR、AND");
            /** @var \Drupal\Core\Entity\Query\ConditionInterface $cgQueryGroup */
            $cgQueryGroup = $entity_query->$func_name(); // 调用条件组
            if (!isset($value["value"])||empty($value["value"])) continue;
            foreach ($value["value"] as $i => $cgItem){
              $cgv = $cgItem;
              $cgop = $operator;
              if (is_array($cgItem)) {
                [$cgv, $cgop] = [$cgItem["value"],$cgItem["operator"]];
              }
              if (strtoupper($cgop) == "IN") $cgv = (array)$cgv; # 如果操作符为IN，则强制转换为array类型
              $cgQueryGroup->condition($i,$cgv, $cgop);
            }
            $entity_query->condition($cgQueryGroup);  // 设置条件组
            continue; # 处理完成，延续下一次循环
          }
          $operator = $value["operator"];
          $value = $value["value"];
        }
        if (strtoupper($operator) == "IN") $value = (array)$value;
        $entity_query->condition($name, $value, $operator);
        $operator = $tmp_operator;
      }
    }
    $result = $entity_query->execute();
    if ($isRenterNid){
      return $result;
    }
    return $result ? $Storage->loadMultiple($result) : [];
  }

  public static function getModulePath($moduleName){
    return self::getModule($moduleName)->getPath();
  }

  public static function getModule($moduleName){
    return self::getService("module_handler")->getModule($moduleName);
  }

  public static function moduleRoot(){
    return self::getModulePath("annotation_route");
  }

  public static function cache(){
    return \Drupal::cache("data");
  }

  /**
   * 查找所有分类下的子类
   * @param $taxonomy_term_id
   * @param array $values
   * @param bool $isIncludingMyself
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function findTaxonomyTermChild($taxonomy_term_id, &$values=[],$isIncludingMyself=true){
    $childTaxonomyTerms = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadChildren($taxonomy_term_id);
    if ($isIncludingMyself) $values[$taxonomy_term_id] = (string)$taxonomy_term_id;
    foreach ($childTaxonomyTerms as $index => $taxonomyTerm){
      $child = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadChildren($index);
      if (!empty($child)) {
        self::findTaxonomyTermChild($index,$values);
      } else {
        $values[$index] = (string)$index;
      }
    }
  }

  /**
   * @return ConfigFactory|object|null
   */
  public static function ConfigFactory(){
    return self::getService('config.factory');
  }

  /**
   * @return Service\AnnotationService|object|null
   */
  public static function AnnotationService(){
    return self::getContainer()->get("annotation_route.annotation_service");
  }

}
