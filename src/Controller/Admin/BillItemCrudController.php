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
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;

/**
 * 账单明细CRUD控制器
 *
 * @extends AbstractCrudController<BillItem>
 */
#[AdminCrud(
    routePath: '/bill-order/item',
    routeName: 'bill_order_item'
)]
final class BillItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BillItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('账单明细')
            ->setEntityLabelInPlural('账单明细管理')
            ->setPageTitle(Crud::PAGE_INDEX, '账单明细列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建账单明细')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑账单明细')
            ->setPageTitle(Crud::PAGE_DETAIL, '账单明细详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['productName', 'productId', 'remark'])
            ->setHelp('index', '管理账单中的明细项目')
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield AssociationField::new('bill', '所属账单')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->hideWhenUpdating()
        ;

        yield TextField::new('productId', '产品ID')
            ->setHelp('产品的唯一标识符')
        ;

        yield TextField::new('productName', '产品名称')
            ->setHelp('产品的显示名称')
        ;

        $statusField = EnumField::new('status', '状态');
        $statusField->setEnumCases(BillItemStatus::cases());

        // 配置徽章显示映射
        $badgeMapping = [];
        foreach (BillItemStatus::cases() as $case) {
            $badgeMapping[$case->value] = $case->getBadge();
        }
        $statusField->renderAsBadges($badgeMapping);

        yield $statusField;

        yield MoneyField::new('price', '单价')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('产品的单价')
        ;

        yield IntegerField::new('quantity', '数量')
            ->setHelp('购买的数量')
        ;

        yield MoneyField::new('subtotal', '小计')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('单价 × 数量')
            ->hideOnForm()
        ;

        yield TextareaField::new('remark', '备注')
            ->hideOnIndex()
            ->setNumOfRows(3)
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
            ->add(EntityFilter::new('bill', '所属账单'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices([
                '待处理' => BillItemStatus::PENDING->value,
                '已处理' => BillItemStatus::PROCESSED->value,
                '已退款' => BillItemStatus::REFUNDED->value,
                '已取消' => BillItemStatus::CANCELLED->value,
            ]))
            ->add(TextFilter::new('productName', '产品名称'))
            ->add(TextFilter::new('productId', '产品ID'))
            ->add(NumericFilter::new('price', '单价'))
            ->add(NumericFilter::new('quantity', '数量'))
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
