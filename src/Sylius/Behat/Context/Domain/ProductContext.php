<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Behat\Context\Domain;

use Behat\Behat\Context\Context;
use Doctrine\DBAL\DBALException;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Core\Test\Services\SharedStorageInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @author Łukasz Chruściel <lukasz.chrusciel@lakion.com>
 * @author Magdalena Banasiak <magdalena.banasiak@lakion.com>
 */
final class ProductContext implements Context
{
    /**
     * @var SharedStorageInterface
     */
    private $sharedStorage;

    /**
     * @var RepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductVariantRepositoryInterface
     */
    private $productVariantRepository;

    /**
     * @var RepositoryInterface
     */
    private $reviewRepository;

    /**
     * @param SharedStorageInterface $sharedStorage
     * @param RepositoryInterface $productRepository
     * @param ProductVariantRepositoryInterface $productVariantRepository
     * @param RepositoryInterface $reviewRepository
     */
    public function __construct(
        SharedStorageInterface $sharedStorage,
        RepositoryInterface $productRepository,
        ProductVariantRepositoryInterface $productVariantRepository,
        RepositoryInterface $reviewRepository
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->reviewRepository = $reviewRepository;
    }

    /**
     * @When /^I delete the ("[^"]+" variant of product "[^"]+")$/
     */
    public function iDeleteTheVariantOfProduct(ProductVariantInterface $productVariant)
    {
        $this->sharedStorage->set('product_variant_id', $productVariant->getId());
        $this->productVariantRepository->remove($productVariant);
    }

    /**
     * @When /^I try to delete the ("[^"]+" variant of product "[^"]+")$/
     */
    public function iTryToDeleteTheVariantOfProduct(ProductVariantInterface $productVariant)
    {
        try {
            $this->productVariantRepository->remove($productVariant);
        } catch (DBALException $exception) {
            $this->sharedStorage->set('last_exception', $exception);
        }
    }

    /**
     * @When /^I try to delete the ("([^"]+)" product)$/
     */
    public function iTryToDeleteTheProduct(ProductInterface $product)
    {
        try {
            $this->productRepository->remove($product);
        } catch (DBALException $exception) {
            $this->sharedStorage->set('last_exception', $exception);
        }
    }

    /**
     * @When I should be notified that this variant is in use and cannot be deleted
     */
    public function iShouldBeNotifiedThatThisVariantIsInUseAndCannotBeDeleted()
    {
        expect($this->sharedStorage->get('last_exception'))->toHaveType(DBALException::class);
    }

    /**
     * @When I should be notified that this product is in use and cannot be deleted
     */
    public function iShouldBeNotifiedThatThisProductIsInUseAndCannotBeDeleted()
    {
        expect($this->sharedStorage->get('last_exception'))->toHaveType(DBALException::class);
    }

    /**
     * @Then /^this variant should not exist in the product catalog$/
     */
    public function productVariantShouldNotExistInTheProductCatalog()
    {
        $productVariantId = $this->sharedStorage->get('product_variant_id');
        $productVariant = $this->productVariantRepository->find($productVariantId);

        expect($productVariant)->toBe(null);
    }

    /**
     * @Then /^(this variant) should still exist in the product catalog$/
     */
    public function productVariantShouldExistInTheProductCatalog(ProductVariantInterface $productVariant)
    {
        $productVariant = $this->productVariantRepository->find($productVariant->getId());

        expect($productVariant)->toNotBe(null);
    }

    /**
     * @Then /^(this product) should still exist in the product catalog$/
     */
    public function productShouldExistInTheProductCatalog(ProductInterface $product)
    {
        $product = $this->productRepository->find($product->getId());

        expect($product)->toNotBe(null);
    }

    /**
     * @When /^I delete the ("[^"]+" product)$/
     */
    public function iDeleteTheProduct(ProductInterface $product)
    {
        try {
            $this->sharedStorage->set('deleted_product', $product);
            $this->productRepository->remove($product);
        } catch (DBALException $exception) {
            $this->sharedStorage->set('last_exception', $exception);
        }
    }

    /**
     * @Then /^there should be no reviews of (this product)$/
     */
    public function thereAreNoProductReviews(ProductInterface $product)
    {
        expect($this->reviewRepository->findBy(['reviewSubject' => $product]))->toBe([]);
    }

    /**
     * @Then /^there should be no variants of (this product) in the product catalog$/
     */
    public function thereAreNoVariants(ProductInterface $product)
    {
        expect($this->productVariantRepository->findBy(['object' => $product]))->toBe([]);
    }
}
