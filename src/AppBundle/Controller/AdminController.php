<?php
namespace AppBundle\Controller;

//use Guzzle\Http\Message\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Entity;
use Symfony\Component\Validator\Constraints\Time;

//use Liuggio\ExcelBundle;

//use Symfony\Component\Validator\Constraints\Page;

class AdminController extends Controller
{
    protected $pageSize = 30;
    //页面模板地址
    protected $pageTemplate = array(
        'page.html.twig' => 'page.html.twig'
    );

    /**
     * @Route("/admin/", name="admin_index")
     */
    public function indexAction()
    {
        return $this->render('AppBundle:admin:index.html.twig');
    }
    /**
     * @Route("/admin/creation/{fixed}", name="admin_creation")
     */
    public function creationAction(Request $request, $fixed = 'n')
    {
        if($fixed == 'n'){
            $repository = $this->getDoctrine()->getRepository('AppBundle:Creation');
        }
        else{
            $repository = $this->getDoctrine()->getRepository('AppBundle:FixedCreation');
        }
        $queryBuilder = $repository->createQueryBuilder('a');
        $queryBuilder->orderBy('a.createTime', 'DESC');
        $query = $queryBuilder->getQuery();
        $paginator  = $this->get('knp_paginator');

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1),/*page number*/
            $this->pageSize
        );
        return $this->render('AppBundle:admin:creation.html.twig', array('pagination'=>$pagination));
    }
    /**
     * @Route("/admin/win/", name="admin_win")
     */
    public function winAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Creation');
        $queryBuilder = $repository->createQueryBuilder('a')
            ->where('a.isWin = 1');
        $queryBuilder->orderBy('a.createTime', 'DESC');
        $query = $queryBuilder->getQuery();
        $paginator  = $this->get('knp_paginator');

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1),/*page number*/
            $this->pageSize
        );
        return $this->render('AppBundle:admin:win.html.twig', array('pagination'=>$pagination));
    }
    /**
     * @Route("/admin/user/", name="admin_user")
     */
    public function wechatUserAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:WechatUser');
        $queryBuilder = $repository->createQueryBuilder('a');
        $queryBuilder->orderBy('a.createTime', 'DESC');
        $query = $queryBuilder->getQuery();
        $paginator  = $this->get('knp_paginator');

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1),/*page number*/
            $this->pageSize
        );
        return $this->render('AppBundle:admin:wechatUser.html.twig', array('pagination'=>$pagination));

    }
    /**
     * @Route("/admin/log/{fixed}", name="admin_like_log")
     */
    public function likeLogAction(Request $request, $fixed='n')
    {
        if($fixed == 'n'){
            $repository = $this->getDoctrine()->getRepository('AppBundle:LikeLog');
        }
        else{
            $repository = $this->getDoctrine()->getRepository('AppBundle:FixedLikeLog');
        }
        $queryBuilder = $repository->createQueryBuilder('a');
        $queryBuilder->orderBy('a.createTime', 'DESC');
        $query = $queryBuilder->getQuery();
        $paginator  = $this->get('knp_paginator');

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1),/*page number*/
            $this->pageSize
        );
        return $this->render('AppBundle:admin:log.html.twig', array('pagination'=>$pagination));
    }
    /**
     * @Route("/admin/export/", name="admin_export")
     */
    public function exportAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:LotteryLog');
        $queryBuilder = $repository->createQueryBuilder('a')
            ->leftjoin('a.lottery','b')
            ->leftjoin('b.prize','c');
        $queryBuilder->where('c.id != 5');
        $queryBuilder->orderBy('a.createTime', 'DESC');
        $logs = $queryBuilder->getQuery()->getResult();
        //$output = '';
        $arr = array(
            'id,奖项,姓名,手机,地址,抽奖时间,抽奖IP'
        );
        foreach($logs as $v){
            $member = $em->getRepository('AppBundle:Member')->findOneBySessionId($v->getSessionId());
            $_string = $v->getId().','.$v->getLottery()->getPrize()->getTitle().',';
            if( isset($member))
                $_string .= $member->getName().','.$member->getTel().','.$member->getAddress().',';
            else
                $_string .= '-,-,-,';
            $_string .= $v->getCreateTime()->format('Y-m-d H:i:s').','.$v->getCreateIp().',';
            $arr[] = $_string;
        }
        $output = implode("\n", $arr);

        //$phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        /*
        $phpExcelObject = new \PHPExcel();
        $phpExcelObject->getProperties()->setCreator("liuggio")
            ->setLastModifiedBy("Giulio De Donato")
            ->setTitle("Office 2005 XLSX Test Document")
            ->setSubject("Office 2005 XLSX Test Document")
            ->setDescription("Test document for Office 2005 XLSX, generated using PHP classes.")
            ->setKeywords("office 2005 openxml php")
            ->setCategory("Test result file");
        $phpExcelObject->setActiveSheetIndex(0);
        foreach($logs as $v){
            $phpExcelObject->setCellValue('A1', $v->getId());
        }
        $phpExcelObject->getActiveSheet()->setTitle('Simple');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'stream-file.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
        */

        $response = new Response($output);
        $response->headers->set('Content-Disposition', ':attachment; filename=data.csv');
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        return $response;
    }

}
