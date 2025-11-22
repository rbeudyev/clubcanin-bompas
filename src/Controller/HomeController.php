<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Result;
use App\Entity\Intervenant;
use App\Repository\EventRepository;
use App\Repository\ResultRepository;
use App\Repository\IntervenantRepository;
use App\Repository\InformationsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private ResultRepository $resultRepository,
        private IntervenantRepository $intervenantRepository,
        private InformationsRepository $informationsRepository
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Récupérer les informations les plus récentes (les 3 premières)
        $informations = $this->informationsRepository->findBy([], ['id' => 'DESC'], 3);
        
        // Récupérer tous les événements
        $events = $this->eventRepository->findBy([], ['date' => 'DESC']);
        
        // Récupérer tous les résultats
        $results = $this->resultRepository->findBy([], ['eventDate' => 'DESC']);
        
        // Récupérer tous les intervenants
        $intervenants = $this->intervenantRepository->findAll();

        return $this->render('home/index.html.twig', [
            'informations' => $informations,
            'events' => $events,
            'results' => $results,
            'intervenants' => $intervenants,
        ]);
    }

    #[Route('/presentation', name: 'app_presentation')]
    public function presentation(): Response
    {
        // Récupérer les intervenants depuis la base de données
        $intervenants = $this->intervenantRepository->findAll();

        // Données des activités (statiques car ce sont des informations générales)
        $activites = [
            [
                'nom' => 'École du chiot',
                'age' => '3-6 mois',
                'description' => 'Socialisation et apprentissage des bases',
                'objectifs' => ['Mise en place de l\'écoute', 'Socialisation', 'Ateliers de proprioception', 'Jeux'],
                'icone' => 'bi-heart-fill'
            ],
            [
                'nom' => 'Éducation Jeunes',
                'age' => '6-12 mois',
                'description' => 'Formalisation des exercices et initiation aux tricks',
                'objectifs' => ['Exercices de base', 'Socialisation', 'Proprioception', 'Tricks'],
                'icone' => 'bi-star-fill'
            ],
            [
                'nom' => 'Éducation Adultes',
                'age' => '+12 mois',
                'description' => 'Perfectionnement et activités spécialisées',
                'objectifs' => ['Exercices avancés', 'Socialisation', 'Proprioception', 'Spécialisations'],
                'icone' => 'bi-award-fill'
            ],
            [
                'nom' => 'Agility Loisir',
                'age' => '+12 mois',
                'description' => 'Parcours d\'agility pour le plaisir',
                'objectifs' => ['Parcours', 'Vitesse', 'Précision', 'Plaisir'],
                'icone' => 'bi-lightning-fill'
            ],
            [
                'nom' => 'Hoopers Loisir',
                'age' => '+12 mois',
                'description' => 'Discipline douce et accessible',
                'objectifs' => ['Mouvement fluide', 'Communication', 'Confiance', 'Plaisir'],
                'icone' => 'bi-circle-fill'
            ],
            [
                'nom' => 'Obé-tricks',
                'age' => '+12 mois',
                'description' => 'Tours et figures amusantes',
                'objectifs' => ['Tricks', 'Créativité', 'Renforcement positif', 'Divertissement'],
                'icone' => 'bi-magic'
            ]
        ];

        return $this->render('home/presentation.html.twig', [
            'intervenants' => $intervenants,
            'activites' => $activites
        ]);
    }
}


