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
        $menu->addChild('user', array('route' => 'admin_user', 'label' => '授权用户'));
        $menu->addChild('form', array('route' => 'admin_form', 'label' => '表单信息'));
        $menu->addChild('answer', array('route' => 'admin_answer_log', 'label' => '答题日志'));
        $menu->addChild('share', array('route' => 'admin_share_log', 'label' => '分享日志'));
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