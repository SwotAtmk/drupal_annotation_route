<?php


namespace Drupal\annotation_route\Form;


use Drupal\annotation_route\Annotation\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HelloWorldForm
 * @package Drupal\annotation_route\Form
 * @Form("/form/hello_world",title="你好，世界")
 */
class HelloWorldForm extends FormBase
{

  public function getFormId()
  {
    return "hello_world_form";
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    return [
      "#type"=>"markup",
      "#markup"=>"hello_world"
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {

  }
}
