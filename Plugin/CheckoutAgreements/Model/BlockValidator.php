<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Plugin\CheckoutAgreements\Model;

/**
 * Block Validator
 */
class BlockValidator
{
    /**
     * Block indicator
     * @var bool
     */
    private $block = false;

    /**
     * aroundIsValid
     *
     * @param \Magento\CheckoutAgreements\Model\AgreementsValidator $subject
     * @param \Closure $proceed
     * @param array $Id
     * @return bool
     */
    public function aroundIsValid(
        \Magento\CheckoutAgreements\Model\AgreementsValidator $subject,
        \Closure $proceed,
        $Id
    ) {
        if ($this->block) {
            return true;
        } else {
            return $proceed($Id);
        }
    }

    /**
     * Set Is Skip Validation
     * @param bool $block
     * @return BlockValidator
     */
    public function setIsSkipValidation(bool $block)
    {
        $this->block = $block;
        return $this;
    }
}
