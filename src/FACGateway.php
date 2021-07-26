<?php
namespace Omnipay\FirstAtlanticCommerce;

use Omnipay\Common\AbstractGateway;
use Omnipay\FirstAtlanticCommerce\Exception\MethodNotSupported;
use Omnipay\Common\Http\ClientInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Omnipay\FirstAtlanticCommerce\Message\TransactionModification;
use Omnipay\FirstAtlanticCommerce\Support\TransactionCode;

chdir(dirname(realpath(__DIR__)));

/**
 * AbstractFACGateway Class
 *
 * @author Ricardo Assing
 * @version 1.0
 */
class FACGateway extends AbstractGateway
implements \Omnipay\FirstAtlanticCommerce\Support\FACParametersInterface
{
    public function __construct(ClientInterface $httpClient = null, HttpRequest $httpRequest = null)
    {
        parent::__construct(null,$httpRequest);
    }

    public function getName()
    {
        return Constants::DRIVER_NAME;
    }

    public function getDefaultParameters()
    {
        $config = include 'src/ConfigArray.php';
        if (array_key_exists(Constants::CONFIG_KEY_FACCUR, $config) && is_array($config[Constants::CONFIG_KEY_FACCUR]))
        {
            $config['currency'] = $config[Constants::CONFIG_KEY_FACCUR][0];
        }

        return $config;
    }

    public function setFacId($FACID)
    {
        return $this->setParameter(Constants::CONFIG_KEY_FACID, $FACID);
    }

    public function getFacId()
    {
        return $this->getParameter(Constants::CONFIG_KEY_FACID);
    }

    public function setFacPwd($PWD)
    {
        return $this->setParameter(Constants::CONFIG_KEY_FACPWD, $PWD);
    }

    public function getFacPwd()
    {
        return $this->getParameter(Constants::CONFIG_KEY_FACPWD);
    }

    public function setFacAcquirer($ACQ)
    {
        return $this->setParameter(Constants::CONFIG_KEY_FACAQID, $ACQ);
    }

    public function getFacAcquirer()
    {
        return $this->getParameter(Constants::CONFIG_KEY_FACAQID);
    }

    public function setFacCurrencyList($list)
    {
        return $this->setParameter(Constants::CONFIG_KEY_FACCUR, $list);
    }

    public function getFacCurrencyList()
    {
        return $this->getParameter(Constants::CONFIG_KEY_FACCUR);
    }

    public function setIntegrationOption($option)
    {
        return $this->setParameter(Constants::GATEWAY_CONFIG_KEY_INTEGRATION,$option);
    }

    public function getIntegrationOption()
    {
        return $this->getParameter(Constants::GATEWAY_CONFIG_KEY_INTEGRATION);
    }

    public function setFacPageSet($PageSet)
    {
        return $this->setParameter(Constants::CONFIG_KEY_FACPGSET, $PageSet);
    }

    public function getFacPageSet()
    {
        return $this->getParameter(Constants::CONFIG_KEY_FACPGSET);
    }

    public function setFacPageName($PageName)
    {
        return $this->setParameter(Constants::CONFIG_KEY_FACPGNAM, $PageName);
    }

    public function getFacPageName()
    {
        return $this->getParameter(Constants::CONFIG_KEY_FACPGNAM);
    }
    
    public function setMerchantResponseURL($url)
    {
        $this->setReturnUrl($url);
        return $this->setParameter(Constants::CONFIG_KEY_MERCHANT_RESPONSE_URL, $url);
    }
    
    public function getMerchantResponseURL()
    {
        return $this->getParameter(Constants::CONFIG_KEY_MERCHANT_RESPONSE_URL);
    }

    public function authorize(array $options = []) : \Omnipay\Common\Message\AbstractRequest
    {
        if (!array_key_exists('transactionCode', $options))
        {
            $options['transactionCode'] = new TransactionCode([TransactionCode::NONE]);
        }

        if (array_key_exists(Constants::AUTHORIZE_OPTION_HOSTED_PAGE, $options) && $options[Constants::AUTHORIZE_OPTION_HOSTED_PAGE] === true)
        {
            return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\HostedPagePreprocess", $options);
        }

        if (array_key_exists(Constants::AUTHORIZE_OPTION_3DS, $options) && $options[Constants::AUTHORIZE_OPTION_3DS] === true)
        {
            return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\Authorize3DS", $options);
        }

        return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\Authorize", $options);
    }

    public function capture(array $options = [])
    {
        return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\TransactionModification", array_merge($options,['modificationType' => TransactionModification::MODIFICATION_TYPE_CAPTURE]));
    }

    public function purchase(array $options = [])
    {
        // Force 3DS
        $options[Constants::AUTHORIZE_OPTION_3DS] = true;
        
        if(array_key_exists('transactionCode', $options) && !($options['transactionCode'])->hasCode(TransactionCode::SINGLE_PASS))
        {
            ($options['transactionCode'])->addCode(TransactionCode::SINGLE_PASS);
        }

        if (!array_key_exists('transactionCode', $options))
        {
            $options['transactionCode'] = new TransactionCode([TransactionCode::SINGLE_PASS]);
        }

        if (array_key_exists(Constants::AUTHORIZE_OPTION_HOSTED_PAGE, $options) && $options[Constants::AUTHORIZE_OPTION_HOSTED_PAGE] === true)
        {
            return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\HostedPagePreprocess", $options);
        }

        if (array_key_exists(Constants::AUTHORIZE_OPTION_3DS, $options) && $options[Constants::AUTHORIZE_OPTION_3DS] === true)
        {
            return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\Authorize3DS", $options);
        }

        return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\Authorize", $options);
    }

    public function hostedPageResults(array $options = [])
    {
        return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\HostedPageResults", $options);
    }

    public function refund(array $options = [])
    {
        return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\TransactionModification", array_merge($options,['modificationType' => TransactionModification::MODIFICATION_TYPE_REFUND]));
    }

    public function void(array $options = [])
    {
        return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\TransactionModification", array_merge($options,['modificationType' => TransactionModification::MODIFICATION_TYPE_REVERSAL]));
    }

    public function fetchTransaction(array $options = [])
    {
        return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\TransactionStatus", $options);
    }
    
    public function acceptNotification(array $options = [])
    {
        if (!array_key_exists("FacPwd", $options))
        {
            $options = array_merge($options,['FacPwd' => $this->getFacPwd()]);
        }
        
        return $this->createRequest("\Omnipay\FirstAtlanticCommerce\Message\AcceptNotification", $options);
    }
    
    public function setReturnUrl($url)
    {
        $this->setMerchantResponseURL($url);
        return $this->setParameter("returnUrl", $url);
    }
    
    public function getReturnUrl()
    {
        return $this->getParameter("returnUrl");
    }

    //TODO Add support for PAN Tokenization
    public function createCard(array $options = [])
    {
        throw new MethodNotSupported(__METHOD__);
    }

    //TODO Add support for PAN Tokenization
    public function updateCard(array $options = [])
    {
        throw new MethodNotSupported(__METHOD__);
    }

    //TODO Add support for PAN Tokenization
    public function deleteCard(array $options = [])
    {
        throw new MethodNotSupported(__METHOD__);
    }
}