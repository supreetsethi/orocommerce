<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CanonicalUrlGenerator
{
    /**
     * @var RoutingInformationProvider
     */
    protected $routingInformationProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var WebsiteUrlResolver
     */
    protected $websiteSystemUrlResolver;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @param ConfigManager $configManager
     * @param CacheProvider $cacheProvider
     * @param RequestStack $requestStack
     * @param RoutingInformationProvider $routingInformationProvider
     * @param WebsiteUrlResolver $websiteSystemUrlResolver
     */
    public function __construct(
        ConfigManager $configManager,
        CacheProvider $cacheProvider,
        RequestStack $requestStack,
        RoutingInformationProvider $routingInformationProvider,
        WebsiteUrlResolver $websiteSystemUrlResolver
    ) {
        $this->configManager = $configManager;
        $this->cacheProvider = $cacheProvider;
        $this->requestStack = $requestStack;
        $this->routingInformationProvider = $routingInformationProvider;
        $this->websiteSystemUrlResolver = $websiteSystemUrlResolver;
    }

    /**
     * @param SluggableInterface $entity
     * @param Localization|null $localization
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getUrl(
        SluggableInterface $entity,
        Localization $localization = null,
        WebsiteInterface $website = null
    ) {
        $url = '';

        if ($this->getCanonicalUrlType($website) === Configuration::DIRECT_URL) {
            $url = $this->getDirectUrl($entity, $localization, $website);
        }

        if (!$url) {
            $url = $this->getSystemUrl($entity, $website);
        }

        return $url;
    }

    /**
     * @param SlugAwareInterface $entity
     * @param Localization|null $localization
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getDirectUrl(
        SlugAwareInterface $entity,
        Localization $localization = null,
        WebsiteInterface $website = null
    ) {
        $url = '';
        $slug = $this->getDirectUrlSlug($entity, $localization);
        if ($slug) {
            $slugUrl = $slug->getUrl();
            $url = $this->getAbsoluteUrl($slugUrl, $website);
        }

        return $url;
    }

    /**
     * @param string $slugUrl
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getAbsoluteUrl($slugUrl, WebsiteInterface $website = null)
    {
        if ($this->getCanonicalUrlSecurityType($website)=== Configuration::SECURE) {
            $domainUrl = $this->websiteSystemUrlResolver->getWebsiteSecureUrl($website);
        } else {
            $domainUrl = $this->websiteSystemUrlResolver->getWebsiteUrl($website);
        }

        $url = $this->createUrl($domainUrl, $slugUrl);

        return $url;
    }

    /**
     * @param SluggableInterface $entity
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getSystemUrl(SluggableInterface $entity, WebsiteInterface $website = null)
    {
        $routeData = $this->routingInformationProvider->getRouteData($entity);

        if ($this->getCanonicalUrlSecurityType($website) === Configuration::SECURE) {
            $url = $this->websiteSystemUrlResolver->getWebsiteSecurePath(
                $routeData->getRoute(),
                $routeData->getRouteParameters(),
                $website
            );
        } else {
            $url = $this->websiteSystemUrlResolver->getWebsitePath(
                $routeData->getRoute(),
                $routeData->getRouteParameters(),
                $website
            );
        }

        return $url;
    }

    /**
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getCanonicalUrlType(WebsiteInterface $website = null)
    {
        $configKey = 'oro_redirect.' . Configuration::CANONICAL_URL_TYPE;
        if (!$this->cacheProvider->contains($configKey)) {
            $this->cacheProvider->save($configKey, $this->configManager->get($configKey, false, false, $website));
        }

        return $this->cacheProvider->fetch($configKey);
    }

    /**
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getCanonicalUrlSecurityType(WebsiteInterface $website = null)
    {
        $configKey = 'oro_redirect.' . Configuration::CANONICAL_URL_SECURITY_TYPE;
        if (!$this->cacheProvider->contains($configKey)) {
            $this->cacheProvider->save($configKey, $this->configManager->get($configKey, false, false, $website));
        }

        return $this->cacheProvider->fetch($configKey);
    }

    /**
     * @param SlugAwareInterface $entity
     * @param Localization $localization
     *
     * @return null|Slug
     */
    protected function getDirectUrlSlug(SlugAwareInterface $entity, Localization $localization = null)
    {
        if ($localization) {
            $slug = $entity->getSlugByLocalization($localization);
        } else {
            $slug = $entity->getBaseSlug();
        }

        return $slug;
    }

    /**
     * @param string $domainUrl
     * @param string $url
     * @return string
     */
    private function createUrl($domainUrl, $url)
    {
        $baseUrl = '';
        if ($masterRequest = $this->requestStack->getMasterRequest()) {
            $baseUrl = $masterRequest->getBaseUrl();
            $baseUrl = trim($baseUrl, '/');
        }

        $urlParts = [rtrim($domainUrl, ' /') ];
        if ($baseUrl) {
            $urlParts[] = $baseUrl;
        }

        $urlParts[] = ltrim($url, '/');
        return  implode('/', $urlParts);
    }
}
