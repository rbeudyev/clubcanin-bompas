<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Result;
use App\Entity\Intervenant;
use App\Entity\Adherent;
use App\Entity\Informations;
use App\Form\BulkEmailType;
use App\Form\EventType;
use App\Form\ResultType;
use App\Form\IntervenantType;
use App\Form\AdherentType;
use App\Form\InformationsType;
use App\Repository\EventRepository;
use App\Repository\ResultRepository;
use App\Repository\IntervenantRepository;
use App\Repository\AdherentRepository;
use App\Repository\InformationsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private ResultRepository $resultRepository,
        private IntervenantRepository $intervenantRepository,
        private AdherentRepository $adherentRepository,
        private InformationsRepository $informationsRepository,
        private ParameterBagInterface $parameterBag
    ) {}

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $events = $this->eventRepository->findBy([], ['date' => 'DESC'], 5);
        $results = $this->resultRepository->findBy([], ['eventDate' => 'DESC'], 5);
        $intervenants = $this->intervenantRepository->findAll();
        $adherents = $this->adherentRepository->findBy([], ['dateAdhesion' => 'DESC'], 5);
        $informations = $this->informationsRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/index.html.twig', [
            'events' => $events,
            'results' => $results,
            'intervenants' => $intervenants,
            'adherents' => $adherents,
            'informations' => $informations,
        ]);
    }

    #[Route('/admin/adherents/email', name: 'app_admin_adherent_email', methods: ['GET', 'POST'])]
    public function emailAdherents(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(BulkEmailType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $recipients = array_values(array_filter(array_unique(array_map(
                static fn (Adherent $adherent): ?string => $adherent->getEmail(),
                $this->adherentRepository->findAll()
            )), static function (?string $email): bool {
                return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                $this->addFlash('error', 'Aucune adresse e-mail valide n’a été trouvée parmi les adhérents.');
            } else {
                $sentCount = 0;

                try {
                    foreach ($recipients as $recipient) {
                        $email = (new Email())
                            ->from($this->getSenderAddress())
                            ->to($recipient)
                            ->subject($data['subject'])
                            ->text($data['content'])
                            ->html($this->renderView('emails/adherents/broadcast.html.twig', [
                                'content' => $data['content'],
                            ]));

                        $mailer->send($email);
                        ++$sentCount;
                    }

                    $this->addFlash('success', sprintf('Message envoyé à %d adhérent(s).', $sentCount));
                    return $this->redirectToRoute('app_admin');
                } catch (TransportExceptionInterface) {
                    $this->addFlash('error', 'Une erreur est survenue pendant l’envoi. Merci de réessayer plus tard.');
                }
            }
        }

        return $this->render('admin/email_adherents.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function getSenderAddress(): string
    {
        return (string) $this->parameterBag->get('mailer_from_address');
    }

    // ========== GESTION DES ÉVÉNEMENTS ==========

    #[Route('/admin/event/new', name: 'app_admin_event_new', methods: ['GET', 'POST'])]
    public function newEvent(Request $request): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/admin/event/{id}/edit', name: 'app_admin_event_edit', methods: ['GET', 'POST'])]
    public function editEvent(Request $request, Event $event): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/admin/event/{id}/delete', name: 'app_admin_event_delete', methods: ['POST'])]
    public function deleteEvent(Event $event): Response
    {
        $this->entityManager->remove($event);
        $this->entityManager->flush();

        $this->addFlash('success', 'Événement supprimé avec succès !');
        return $this->redirectToRoute('app_admin');
    }

    // ========== GESTION DES RÉSULTATS ==========

    #[Route('/admin/result/new', name: 'app_admin_result_new', methods: ['GET', 'POST'])]
    public function newResult(Request $request): Response
    {
        $result = new Result();
        $form = $this->createForm(ResultType::class, $result);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($result);
            $this->entityManager->flush();

            $this->addFlash('success', 'Résultat créé avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('result/new.html.twig', [
            'result' => $result,
            'form' => $form,
        ]);
    }

    #[Route('/admin/result/{id}/edit', name: 'app_admin_result_edit', methods: ['GET', 'POST'])]
    public function editResult(Request $request, Result $result): Response
    {
        $form = $this->createForm(ResultType::class, $result);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Résultat modifié avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('result/edit.html.twig', [
            'result' => $result,
            'form' => $form,
        ]);
    }

    #[Route('/admin/result/{id}/delete', name: 'app_admin_result_delete', methods: ['POST'])]
    public function deleteResult(Result $result): Response
    {
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        $this->addFlash('success', 'Résultat supprimé avec succès !');
        return $this->redirectToRoute('app_admin');
    }

    // ========== GESTION DES INTERVENANTS ==========

    #[Route('/admin/intervenant/new', name: 'app_admin_intervenant_new', methods: ['GET', 'POST'])]
    public function newIntervenant(Request $request): Response
    {
        $intervenant = new Intervenant();
        $form = $this->createForm(IntervenantType::class, $intervenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($intervenant);
            $this->entityManager->flush();

            $this->addFlash('success', 'Intervenant créé avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('intervenant/new.html.twig', [
            'intervenant' => $intervenant,
            'form' => $form,
        ]);
    }

    #[Route('/admin/intervenant/{id}/edit', name: 'app_admin_intervenant_edit', methods: ['GET', 'POST'])]
    public function editIntervenant(Request $request, Intervenant $intervenant): Response
    {
        $form = $this->createForm(IntervenantType::class, $intervenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Intervenant modifié avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('intervenant/edit.html.twig', [
            'intervenant' => $intervenant,
            'form' => $form,
        ]);
    }

    #[Route('/admin/intervenant/{id}/delete', name: 'app_admin_intervenant_delete', methods: ['POST'])]
    public function deleteIntervenant(Intervenant $intervenant): Response
    {
        $this->entityManager->remove($intervenant);
        $this->entityManager->flush();

        $this->addFlash('success', 'Intervenant supprimé avec succès !');
        return $this->redirectToRoute('app_admin');
    }

    // ========== GESTION DES ADHÉRENTS ==========

    #[Route('/admin/adherent/new', name: 'app_admin_adherent_new', methods: ['GET', 'POST'])]
    public function newAdherent(Request $request): Response
    {
        $adherent = new Adherent();
        $form = $this->createForm(AdherentType::class, $adherent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($adherent);
            $this->entityManager->flush();

            $this->addFlash('success', 'Adhérent créé avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('adherent/new.html.twig', [
            'adherent' => $adherent,
            'form' => $form,
        ]);
    }

    #[Route('/admin/adherent/{id}/edit', name: 'app_admin_adherent_edit', methods: ['GET', 'POST'])]
    public function editAdherent(Request $request, Adherent $adherent): Response
    {
        $form = $this->createForm(AdherentType::class, $adherent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Adhérent modifié avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('adherent/edit.html.twig', [
            'adherent' => $adherent,
            'form' => $form,
        ]);
    }

    #[Route('/admin/adherent/{id}/delete', name: 'app_admin_adherent_delete', methods: ['POST'])]
    public function deleteAdherent(Adherent $adherent): Response
    {
        $this->entityManager->remove($adherent);
        $this->entityManager->flush();

        $this->addFlash('success', 'Adhérent supprimé avec succès !');
        return $this->redirectToRoute('app_admin');
    }

    // ========== GESTION DES INFORMATIONS ==========

    #[Route('/admin/information/new', name: 'app_admin_information_new', methods: ['GET', 'POST'])]
    public function newInformation(Request $request): Response
    {
        $information = new Informations();
        $form = $this->createForm(InformationsType::class, $information);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($information);
            $this->entityManager->flush();

            $this->addFlash('success', 'Information créée avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('informations/new.html.twig', [
            'information' => $information,
            'form' => $form,
        ]);
    }

    #[Route('/admin/information/{id}/edit', name: 'app_admin_information_edit', methods: ['GET', 'POST'])]
    public function editInformation(Request $request, Informations $information): Response
    {
        $form = $this->createForm(InformationsType::class, $information);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Information modifiée avec succès !');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('informations/edit.html.twig', [
            'information' => $information,
            'form' => $form,
        ]);
    }

    #[Route('/admin/information/{id}/delete', name: 'app_admin_information_delete', methods: ['POST'])]
    public function deleteInformation(Informations $information): Response
    {
        $this->entityManager->remove($information);
        $this->entityManager->flush();

        $this->addFlash('success', 'Information supprimée avec succès !');
        return $this->redirectToRoute('app_admin');
    }
}