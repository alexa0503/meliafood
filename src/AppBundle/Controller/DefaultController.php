<?php
namespace AppBundle\Controller;

use AppBundle\Wechat\Wechat;
use Imagine\Gd\Imagine;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helper;
use AppBundle\Entity;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;
use Symfony\Component\Validator\Constraints\DateTime;

#use Symfony\Component\Validator\Constraints\Image;

class DefaultController extends Controller
{
    public function getUser()
    {
        $session = $this->get('session');
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:WechatUser')->findOneByOpenId($session->get('open_id'));
        return $user;
    }
    /**
     * @Route("/", name="_index")
     */
    public function indexAction()
    {
        $user = $this->getUser();
        return $this->render('AppBundle:default:index.html.twig',array(
        ));
    }
    /**
     * @Route("/rule", name="_rule")
     */
    public function ruleAction()
    {
        return $this->render('AppBundle:default:rule.html.twig',array(
        ));
    }
    /**
     * @Route("/intro", name="_intro")
     */
    public function introAction(Request $request)
    {
        $session=$request->getSession();

        $user = $this->getUser();

        if($user->getIsFullAnswer() == true){
            $session->set('wx_share_success_url', $this->generateUrl('_form',array('t'=>0)));
        }

        $answer_log = array(1=>false,false,false,false,false);
        if( null != $user->getLogs()){
            foreach ($user->getLogs() as $log) {
                $answer_log[$log->getAnswerType()] = true;
            }
        }
        return $this->render('AppBundle:default:intro.html.twig',array(
            'answer_log' => $answer_log,
            'user' => $user
        ));
    }
    /**
     * @Route("/question/{t}", name="_question")
     */
    public function questionAction($t = 1)
    {
        return $this->render('AppBundle:default:question.html.twig',array(
        ));
    }
    /**
     * @Route("/answer/{t}", name="_answer")
     */
    public function answerAction(Request $request,$t = 1)
    {
        $session=$request->getSession();
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $qb = $em->getRepository('AppBundle:AnswerLog')
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.user = :user AND a.answerType = :answerType')
            ->setParameter('user', $user)
            ->setParameter('answerType', $t);
        $count = $qb->getQuery()->getSingleScalarResult();
        if( $count == 0){
            $answer_log = new Entity\AnswerLog;
            $answer_log->setUser($user);
            $answer_log->setAnswerType($t);
            $answer_log->setCreateTime(new \DateTime("now"));
            $answer_log->setCreateIp($this->container->get('request')->getClientIp());
            $qb = $em->getRepository('AppBundle:AnswerLog')
                ->createQueryBuilder('a')
                ->select('COUNT(a)')
                ->where('a.user = :user')
                ->setParameter('user', $user);
            $count1 = $qb->getQuery()->getSingleScalarResult();
            if($count1 >= 4){
                $user->setIsFullAnswer(true);
                $em->persist($user);
            }
            $em->persist($answer_log);
            $em->flush();

            $session->set('wx_share_success_url', $this->generateUrl('_form',array('t'=>$t)));
        }
        else{
            return $this->redirect( $this->generateUrl('_next'));
        }
        
        return $this->render('AppBundle:default:answer.html.twig',array());
    }


    /**
     * @Route("/next", name="_next")
     */
    public function nextAction(Request $request)
    {
        $user= $this->getUser();
        if(null == $user->getLogs()){
            return $this->redirect( $this->generateUrl('_question',array('t'=>1)));
        }
        elseif( count($user->getLogs()) >= 5){
            return $this->redirect($this->generateUrl('_form'));
        }
        else{
            $all_type = array(1,2,3,4,5);
            $_type = array();
            foreach ($user->getLogs() as $log) {
                $_type[] = $log->getAnswerType();
            }
            $type = array_diff($all_type, $_type);
            return $this->redirect( $this->generateUrl('_question',array('t'=>reset($type))));
        }
    }


    /**
     * @Route("/form/{t}", name="_form")
     */
    public function formAction($t = null)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('AppBundle:Form')
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.user = :user ')
            ->setParameter('user', $user);
        $count = $qb->getQuery()->getSingleScalarResult();
        if( $count != 0)
            return $this->redirect($this->generateUrl('_result'));
         if( null !== $t){
            $em = $this->getDoctrine()->getManager();
            $share_log = new Entity\ShareLog;
            $share_log->setUser($user);
            $share_log->setAnswerType($t);
            $share_log->setCreateTime(new \DateTime("now"));
            $share_log->setCreateIp($this->container->get('request')->getClientIp());
            $em->persist($share_log);
            $em->flush();
        }
        return $this->render('AppBundle:default:form.html.twig');
    }


    /**
     * @Route("/result", name="_result")
     */
    public function resultAction()
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('AppBundle:Form')
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.user = :user ')
            ->setParameter('user', $user);
        $count = $qb->getQuery()->getSingleScalarResult();
        if( $count == 0)
            return $this->redirect($this->generateUrl('_form'));
        return $this->render('AppBundle:default:result.html.twig');
    }



    /**
     * @Route("/form/post", name="_form_post")
     */
    public function formPostAction(Request $request)
    {
        $result = array(
            'ret' => 0,
            'msg' => '',
        );
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('AppBundle:Form')
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.user = :user ')
            ->setParameter('user', $user);
        $count = $qb->getQuery()->getSingleScalarResult();
        if( $count == 0){
            if($request->getMethod() == "POST"){
                if($request->get('username') == null){
                    $result['ret'] = 1002;
                    $result['msg'] = '姓名不能为空';
                }
                elseif($request->get('email') == null){
                    $result['ret'] = 1003;
                    $result['msg'] = 'Email不能为空';
                }
                elseif($request->get('mobile') == null){
                    $result['ret'] = 1004;
                    $result['msg'] = '手机号码不能为空';
                }
                else{
                    $form = new Entity\Form;
                    $form->setUser($user);
                    $form->setUsername($request->get('username'));
                    $form->setEmail($request->get('email'));
                    $form->setMobile($request->get('mobile'));
                    $form->setCreateTime(new \DateTime("now"));
                    $form->setCreateIp($this->container->get('request')->getClientIp());
                    $em->persist($form);
                    $em->flush();
                }
            }
            else{
                $result['ret'] = 1005;
                $result['msg'] = '来源不正确';
            }
        }
        else{
            $result = array(
                'ret' => 1001,
                'msg' => '您已经提交过表单了~',
            );
        }
        return new Response(json_encode($result));
    }





    /**
     * @Route("callback/", name="_callback")
     */
    public function callbackAction(Request $request)
    {
        $session = $request->getSession();
        $code = $request->query->get('code');
        //$state = $request->query->get('state');
        $app_id = $this->container->getParameter('wechat_appid');
        $secret = $this->container->getParameter('wechat_secret');
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $app_id . "&secret=" . $secret . "&code=$code&grant_type=authorization_code";
        $data = Helper\HttpClient::get($url);
        $token = json_decode($data);
        //$session->set('open_id', null);
        if ( isset($token->errcode) && $token->errcode != 0) {
            return new Response('something bad !');
        }

        $wechat_token = $token->access_token;
        $wechat_openid = $token->openid;
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$wechat_token}&openid={$wechat_openid}";
        $data = Helper\HttpClient::get($url);
        $user_data = json_decode($data);

        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        try{
            $session->set('open_id', $user_data->openid);
            $repo = $em->getRepository('AppBundle:WechatUser');
            $qb = $repo->createQueryBuilder('a');
            $qb->select('COUNT(a)');
            $qb->where('a.openId = :openId');
            $qb->setParameter('openId', $user_data->openid);
            $count = $qb->getQuery()->getSingleScalarResult();
            if($count <= 0){
                $wechat_user = new Entity\WechatUser();
                $wechat_user->setOpenId($wechat_openid);
                $wechat_user->setNickName($user_data->nickname);
                $wechat_user->setCity($user_data->city);
                $wechat_user->setGender($user_data->sex);
                $wechat_user->setProvince($user_data->province);
                $wechat_user->setCountry($user_data->country);
                $wechat_user->setHeadImg($user_data->headimgurl);
                $wechat_user->setCreateIp($request->getClientIp());
                $wechat_user->setCreateTime(new \DateTime('now'));
                $em->persist($wechat_user);
                $em->flush();
            }
            else{
                $wechat_user = $em->getRepository('AppBundle:WechatUser')->findOneBy(array('openId' => $wechat_openid));
                $wechat_user->setHeadImg($user_data->headimgurl);
                $em->persist($wechat_user);
                $em->flush();
                $session->set('user_id', $wechat_user->getId());
            }

            $redirect_url = $session->get('redirect_url') == null ? $this->generateUrl('_index') : $session->get('redirect_url');
            $em->getConnection()->commit();
            return $this->redirect($redirect_url);
        }
        catch (Exception $e) {
            $em->getConnection()->rollback();
            return new Response($e->getMessage());
        }
    }
}
