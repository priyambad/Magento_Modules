<?php

namespace Redington\Configuration\Setup;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\CategoryFactory;

class InstallData implements InstallDataInterface
{

    private $categoryFactory;

    public function __construct(
        CategoryFactory $categoryFactory
    ) {
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Installs data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $categoryData = [
            'Volume' => [
                'category' => ['PC & TABLETS'],
                'sub-category' => ['NOTEBOOKS','DESKTOPS','MEDIA TABLETS','WORKSTATIONS','ACCESSORIES'],
                'brands' => ['HP','DELL','LENOVO','ACER','ASUS','MSI','MICROSOFT','APPLE']
            ],
            'Telco' =>[
                'category' => ['MOBILITY'],
                'sub-category' => ['SMART PHONES','FEATURE PHONES','SMART WATCHES','ACCESSORIES'],
                'brands' => ['APPLE']
            ]
        ];
        foreach ($categoryData as $rootCatName => $categories) {
            $parentCategory = $this->categoryFactory->create()->getCollection()->addFieldToFilter('name',$rootCatName)->getFirstItem();

            $MainCategoryName = $categories['category'][0];
            $mainCategoryId = $this->createCategory($MainCategoryName, $parentCategory, true);
            $parentMainCategory = $this->categoryFactory->create()->load($mainCategoryId);

            foreach ($categories['sub-category'] as $key => $subcategories) {
                $subCategoryName = $subcategories;
                $subCategoryId = $this->createCategory($subCategoryName, $parentMainCategory, true);
                $parentSubCategory = $this->categoryFactory->create()->load($subCategoryId);
                foreach ($categories['brands'] as $brandName) {
                    $brandCategoryName = $brandName;
                    $this->createCategory($brandCategoryName, $parentSubCategory, false);
                }
            }
        }
    }

    public function createCategory($catName, $parentCat, $includeInMenu) {
        $category = $this->categoryFactory->create();
        $category->setName($catName);
        $category->setIsActive(true);
        $category->setIncludeInMenu($includeInMenu);
        $category->setDisplayMode('PRODUCTS');
        $category->setPath($parentCat->getPath());
        $category->setStoreId($parentCat->getStoreId());
        $category->setParentId($parentCat->getId());
        $category->save();
        return $category->getId();
    }
}