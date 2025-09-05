<?php

namespace App\Controller;

use App\Entity\Gateway;
use App\Form\GatewayType;
use App\Repository\GatewayRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gateways')]
#[IsGranted('ROLE_ADMIN')]
class GatewayController extends AbstractController
{
    #[Route('/', name: 'gateway_index', methods: ['GET'])]
    public function index(GatewayRepository $gatewayRepository): Response
    {
        return $this->render('gateway/index.html.twig', [
            'gateways' => $gatewayRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'gateway_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $gateway = new Gateway();
        $form = $this->createForm(GatewayType::class, $gateway, [
            'attr' => ['data-turbo' => 'true'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($gateway);
            $em->flush();
            $this->addFlash('success', 'Gateway created');
            return $this->redirectToRoute('gateway_index');
        }

        return $this->render('gateway/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'gateway_edit', methods: ['GET', 'POST'])]
    public function edit(Gateway $gateway, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(GatewayType::class, $gateway, [
            'attr' => ['data-turbo' => 'true'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Gateway updated');
            return $this->redirectToRoute('gateway_index');
        }

        return $this->render('gateway/edit.html.twig', [
            'form' => $form,
            'gateway' => $gateway,
        ]);
    }

    #[Route('/{id}', name: 'gateway_delete', methods: ['POST'])]
    public function delete(Gateway $gateway, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_gateway_'.$gateway->getId(), (string) $request->request->get('_token'))) {
            $em->remove($gateway);
            $em->flush();
            $this->addFlash('success', 'Gateway deleted');
        }
        return $this->redirectToRoute('gateway_index');
    }
}
