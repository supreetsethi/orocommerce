<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Controller\Frontend;

use Oro\Bundle\FrontendLocalizationBundle\Helper\LocalizedSlugRedirectHelper;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ajax Localization Controller
 */
class AjaxLocalizationController extends AbstractController
{
    use RedirectLocalizationControllerTrait;

    #[Route(
        path: '/set-current-localization',
        name: 'oro_frontend_localization_frontend_set_current_localization',
        methods: ['POST']
    )]
    #[CsrfProtection()]
    public function setCurrentLocalizationAction(Request $request): JsonResponse
    {
        $localization = $this->container->get(LocalizationManager::class)
            ->getLocalization($request->get('localization'), false);

        $localizationManager = $this->container->get(UserLocalizationManager::class);
        if ($localization instanceof Localization
            && array_key_exists($localization->getId(), $localizationManager->getEnabledLocalizations())
        ) {
            $localizationManager->setCurrentLocalization($localization);

            $redirectHelper = $this->container->get('oro_locale.helper.localized_slug_redirect');
            $fromUrl = $this->generateUrlWithContext($request);
            if ($fromUrl) {
                if ($request->server->has('WEBSITE_PATH')) {
                    $toUrl = $this->getUrlForWebsitePath($request, $fromUrl, $localization);
                } else {
                    $toUrl = $redirectHelper->getLocalizedUrl($fromUrl, $localization);
                    $toUrl = $this->rebuildQueryString($toUrl, $request);
                }
            } else {
                return new JsonResponse(['success' => true, 'redirectTo' => $this->generateUrlByReferer($request)]);
            }

            return new JsonResponse(['success' => true, 'redirectTo' => $toUrl]);
        }

        return new JsonResponse(['success' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LocalizationManager::class,
                UserLocalizationManager::class,
                'oro_locale.helper.localized_slug_redirect' => LocalizedSlugRedirectHelper::class,
            ]
        );
    }
}
