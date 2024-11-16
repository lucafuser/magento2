<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Ui\Component\Listing\Columns;

use Magento\Catalog\Ui\Component\Listing\Columns\Price;
use Magento\Directory\Model\Currency;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class PriceTest extends TestCase
{
    /**
     * @var ContextInterface|MockObject
     */
    private $context;

    /**
     * @var UiComponentFactory|MockObject
     */
    private $uiComponentFactory;

    /**
     * @var CurrencyInterface|MockObject
     */
    private $localeCurrency;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var Price
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->context = $this->createMock(ContextInterface::class);
        $this->uiComponentFactory = $this->createMock(UiComponentFactory::class);
        $this->localeCurrency = $this->createMock(CurrencyInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $this->model = new Price(
            $this->context,
            $this->uiComponentFactory,
            $this->localeCurrency,
            $this->storeManager,
            [],
            ['name' => 'price'],
            $this->priceCurrency
        );
    }

    /**
     * @param array $input
     * @param array $output
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     * @dataProvider prepareDataSourceDataProvider
     */
    public function testPrepareDataSource(array $input, array $output): void
    {
        $storeId = Store::DEFAULT_STORE_ID;
        $this->context->expects($this->once())->method('getFilterParam')->willReturn($storeId);

        $store = $this->createMock(Store::class);
        $this->storeManager->expects($this->once())->method('getStore')->with($storeId)->willReturn($store);

        $currency = $this->createMock(Currency::class);
        $store->expects($this->once())->method('getCurrentCurrency')->willReturn($currency);
        $store->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $currency->expects($this->once())->method('getCode')->willReturn('USD');
        $this->priceCurrency->expects($this->never())->method('convert');

        $this->priceCurrency->method('format')
            ->willReturn('formatted');
        $this->assertEquals($output, $this->model->prepareDataSource($input));
    }

    /**
     * @param array $input
     * @param array $output
     * @return void
     * @throws Exception
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @dataProvider prepareDataSourceDataProvider
     */
    public function testPrepareDataSourceWithPriceConversion(array $input, array $output): void
    {
        $storeId = Store::DEFAULT_STORE_ID;
        $price = $input['data']['items'][0]['price'];
        $this->context->expects($this->once())->method('getFilterParam')->willReturn($storeId);

        $store = $this->createMock(Store::class);
        $this->storeManager->expects($this->once())->method('getStore')->with($storeId)->willReturn($store);

        $currency = $this->createMock(Currency::class);
        $store->expects($this->once())->method('getCurrentCurrency')->willReturn($currency);
        $store->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $currency->expects($this->once())->method('getCode')->willReturn('EUR');

        $this->priceCurrency->expects($this->once())->method('convert')->with($price, $store, $currency);

        $this->priceCurrency->method('format')
            ->willReturn('formatted');
        $this->assertEquals($output, $this->model->prepareDataSource($input));
    }

    /**
     * @return array
     */
    public static function prepareDataSourceDataProvider(): array
    {
        return [
            [
                [
                    'data' => [
                        'items' => [
                            [
                                'price' => '10.00'
                            ]
                        ]
                    ]
                ],
                [
                    'data' => [
                        'items' => [
                            [
                                'price' => 'formatted'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
