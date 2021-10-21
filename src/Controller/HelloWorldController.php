<?php


namespace Drupal\annotation_route\Controller;


use Drupal\annotation_route\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HelloWorldController
 * @package Drupal\annotation_route\Controller
 * @Route("/hello_world")
 */
class HelloWorldController extends BaseController
{
    /**
     * @Route("/hi")
     */
    public function helloAction(){
      return new Response(t("hello world"));
    }

  /**
   * @return Response|null
   * @Route("/twig")
   */
    public function helloTwigAction(){
      return $this->render("@annotation/hello_world.html.twig");
    }

}
