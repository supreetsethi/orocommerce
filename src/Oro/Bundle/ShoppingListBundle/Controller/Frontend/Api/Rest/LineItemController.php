<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for shopping list line item REST API requests.
 * @NamePrefix("oro_api_shopping_list_frontend_")
 */
class LineItemController extends RestController implements ClassResourceInterface
{
    /**
     * @Delete(requirements={"id"="\d+"})
     *
     * @ApiDoc(
     *      description="Delete Line Item",
     *      resource=true
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction(int $id)
    {
        $success = false;
        /** @var LineItem $lineItem */
        $lineItem = $this->getDoctrine()
            ->getManagerForClass('OroShoppingListBundle:LineItem')
            ->getRepository('OroShoppingListBundle:LineItem')
            ->find($id);

        $view = $this->view(null, Response::HTTP_NO_CONTENT);

        if ($lineItem) {
            if (!$this->isGranted('EDIT', $lineItem->getShoppingList())) {
                $view = $this->view(null, Response::HTTP_FORBIDDEN);
            } else {
                $this->get('oro_shopping_list.manager.shopping_list')->removeLineItem($lineItem);
                $success = true;
            }
        }

        return $this->buildResponse($view, self::ACTION_DELETE, ['id' => $lineItem->getId(), 'success' => $success]);
    }

    /**
     * @Put(requirements={"id"="\d+"})
     *
     * @ApiDoc(
     *      description="Update Line Item",
     *      resource=true
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param int $id
     *
     * @param Request $request
     * @return Response
     */
    public function putAction(int $id, Request $request)
    {
        /** @var LineItem $entity */
        $entity = $this->getManager()->find($id);

        if ($entity) {
            $form = $this->createForm(FrontendLineItemType::class, $entity, ['csrf_protection' => false]);

            $handler = new LineItemHandler(
                $form,
                $request,
                $this->getDoctrine(),
                $this->get('oro_shopping_list.manager.shopping_list'),
                $this->get('oro_shopping_list.manager.current_shopping_list'),
                $this->get('validator')
            );
            $isFormHandled = $handler->process($entity);
            if ($isFormHandled) {
                $view = $this->view(
                    ['unit' => $entity->getUnit()->getCode(), 'quantity' => $entity->getQuantity()],
                    Response::HTTP_OK
                );
            } else {
                $view = $this->view($form, Response::HTTP_BAD_REQUEST);
            }

            if (!$this->isGranted('EDIT', $entity->getShoppingList())) {
                $view = $this->view(null, Response::HTTP_FORBIDDEN);
            }
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_shopping_list.line_item.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
