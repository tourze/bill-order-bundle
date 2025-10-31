<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;

/**
 * 账单CRUD控制器
 *
 * @extends AbstractCrudController<BillOrder>
 */
#[AdminCrud(
    routePath: '/bill-order/order',
    routeName: 'bill_order_order'
)]
final class BillOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BillOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('账单')
            ->setEntityLabelInPlural('账单管理')
            ->setPageTitle(Crud::PAGE_INDEX, '账单列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建账单')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑账单')
            ->setPageTitle(Crud::PAGE_DETAIL, '账单详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['title', 'billNumber', 'remark'])
            ->setHelp('index', '管理系统中的所有账单订单')
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield TextField::new('title', '账单标题')
            ->setHelp('账单的描述性标题')
        ;

        yield TextField::new('billNumber', '账单编号')
            ->hideOnIndex()
            ->setHelp('用于标识账单的唯一编号')
        ;

        $statusField = EnumField::new('status', '状态');
        $statusField->setEnumCases(BillOrderStatus::cases());

        // 配置徽章显示映射
        $badgeMapping = [];
        foreach (BillOrderStatus::cases() as $case) {
            $badgeMapping[$case->value] = $case->getBadge();
        }
        $statusField->renderAsBadges($badgeMapping);

        yield $statusField;

        yield MoneyField::new('totalAmount', '总金额')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
        ;

        yield AssociationField::new('items', '账单明细')
            ->onlyOnDetail()
            ->setTemplatePath('@BillOrder/admin/field/bill_items.html.twig')
        ;

        yield TextareaField::new('remark', '备注')
            ->hideOnIndex()
            ->setNumOfRows(3)
        ;

        yield DateTimeField::new('payTime', '付款时间')
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->onlyOnDetail()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', '状态')->setChoices([
                '草稿' => BillOrderStatus::DRAFT->value,
                '待付款' => BillOrderStatus::PENDING->value,
                '已付款' => BillOrderStatus::PAID->value,
                '已完成' => BillOrderStatus::COMPLETED->value,
                '已取消' => BillOrderStatus::CANCELLED->value,
            ]))
            ->add(TextFilter::new('title', '标题'))
            ->add(TextFilter::new('billNumber', '账单编号'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('payTime', '付款时间'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }
}
