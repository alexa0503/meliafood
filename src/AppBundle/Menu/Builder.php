<?php
namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    /**
     * 后台主菜单
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked nav-bracket');
        $menu->setChildrenAttribute('id', 'leftmenu');

        $menu->addChild('Dashboard', array('route' => 'admin_index'));
        $creation = $menu->addChild('creation', array('route' => 'admin_creation', 'label' => '作品信息'));
        $creation->setAttribute('class', 'nav-parent');
        $creation->setChildrenAttribute('class', 'children');
        $creation->addChild('creation', array('route' => 'admin_creation',  'routeParameters' => array('fixed'=>'n'),  'label' => '用户作品'));
        $creation->addChild('fixedCreation', array('route' => 'admin_creation', 'routeParameters' => array('fixed'=>'y'), 'label' => '首页作品'));
        $menu->addChild('user', array('route' => 'admin_user', 'label' => '授权用户'));
        $log = $menu->addChild('creationLog', array('route' => 'admin_like_log', 'label' => '点赞日志'));
        $log->setAttribute('class', 'nav-parent');
        $log->setChildrenAttribute('class', 'children');
        $log->addChild('likeLog', array('route' => 'admin_like_log','routeParameters' => array('fixed'=>'n'), 'label' => '用户作品'));
        $log->addChild('fixedLikeLog', array('route' => 'admin_like_log', 'routeParameters' => array('fixed'=>'y'), 'label' => '首页作品'));
        $menu->addChild('win', array('route' => 'admin_win', 'label' => '中奖作品'));
        /*
        $catalog_log = $menu->addChild('lotteryLog', array('route' => 'admin_log', 'label' => '中奖记录'));
        $catalog_log->setAttribute('class', 'nav-parent');
        $catalog_log->setChildrenAttribute('class', 'children');
        $catalog_log->addChild('win', array('route' => 'admin_log','routeParameters' => array('win'=>'y'), 'label' => '已中奖记录'));
        $catalog_log->addChild('noWin', array('route' => 'admin_log', 'routeParameters' => array('win'=>'n'), 'label' => '未中奖'));
        $menu->addChild('member', array('route' => 'admin_member', 'label' => '提交信息'));
        $menu->addChild('prize', array('route' => 'admin_prize', 'label' => '奖项信息'));
        $menu->addChild('lottery', array('route' => 'admin_lottery', 'label' => '抽奖设置'));
        */
        return $menu;
    }
}