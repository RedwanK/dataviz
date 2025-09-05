<?php

namespace App\Controller;

use App\Entity\Device;
use App\Repository\DeviceRepository;
use App\Entity\Gateway;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/devices')]
#[IsGranted('ROLE_ADMIN')]
class DeviceController extends AbstractController
{
    #[Route('/', name: 'device_index', methods: ['GET'])]
    public function index(DeviceRepository $deviceRepository): Response
    {
        $devices = $deviceRepository->findBy([], ['id' => 'DESC']);

        return $this->render('device/index.html.twig', [
            'devices' => $devices,
        ]);
    }

    #[Route('/gateway/{id}', name: 'device_by_gateway', methods: ['GET'])]
    public function byGateway(Gateway $gateway, DeviceRepository $deviceRepository): Response
    {
        $devices = $deviceRepository->findBy(['gateway' => $gateway], ['id' => 'DESC']);

        return $this->render('device/index.html.twig', [
            'devices' => $devices,
            'filter_gateway' => $gateway,
        ]);
    }

    #[Route('/{id}', name: 'device_show', methods: ['GET'])]
    public function show(Device $device): Response
    {
        return $this->render('device/show.html.twig', [
            'device' => $device,
        ]);
    }
}
